<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Copy;
use Soranoiseki\BookGroup\Models\Library\Loan;
use Soranoiseki\BookGroup\Models\Library\Member;
use Soranoiseki\BookGroup\Services\LibraryService;
use Tests\TestCase;

/**
 * Uses transactions (not RefreshDatabase) so the development database is left untouched.
 */
class LibraryTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $roles = ['library']): User
    {
        // The default UserFactory predates the firstname/lastname columns, so fill them here.
        $user = User::factory()->create(['firstname' => 'Test', 'lastname' => 'User', 'enabled' => true]);
        foreach ($roles as $name) {
            $role = Role::firstOrCreate(['internal_name' => $name], ['name' => $name]);
            $user->roles()->attach($role->id);
        }

        return $user;
    }

    private function book(int $copies = 1, array $attrs = []): Book
    {
        $book = Book::create($attrs + ['code' => 'T' . uniqid(), 'title' => 'Testbuch', 'isbn' => '9780306406157']);
        app(LibraryService::class)->ensureCopies($book, $copies);

        return $book->fresh('copies');
    }

    public function test_page_and_api_need_the_library_role(): void
    {
        $this->actingAs($this->user([]))->getJson('/api/library/summary')->assertUnauthorized();
        $this->actingAs($this->user())->getJson('/api/library/summary')
            ->assertOk()->assertJsonPath('canManage', false);
        $this->actingAs($this->user())->get('/library')->assertOk()->assertSee('app-library', false);
    }

    public function test_lookup_finds_copy_label_isbn_and_code(): void
    {
        $book = $this->book(2);
        $copy = $book->copies[1];
        $user = $this->user();

        $this->actingAs($user)->getJson('/api/library/lookup?code=' . $copy->code)
            ->assertJsonPath('found', true)->assertJsonPath('copyId', $copy->id);
        $this->actingAs($user)->getJson('/api/library/lookup?code=978-0-306-40615-7')
            ->assertJsonPath('found', true)->assertJsonPath('copyId', null)->assertJsonPath('book.id', $book->id);
        $this->actingAs($user)->getJson('/api/library/lookup?code=' . $book->code)->assertJsonPath('found', true);
        $this->actingAs($user)->getJson('/api/library/lookup?code=9780306406164')
            ->assertJsonPath('found', false)->assertJsonPath('isbn', '9780306406164');
    }

    public function test_borrow_return_and_history(): void
    {
        $copy = $this->book()->copies[0];
        $member = Member::create(['first_name' => '三', 'last_name' => '张']);
        $user = $this->user();

        $this->actingAs($user)->postJson("/api/library/copies/{$copy->id}/borrow", ['member_id' => $member->id])
            ->assertOk()->assertJsonPath('book.copies.0.loan.member.name', '张三');

        // a second borrow of the same copy is refused
        $this->actingAs($user)->postJson("/api/library/copies/{$copy->id}/borrow", ['member_id' => $member->id])
            ->assertStatus(409);

        $this->actingAs($user)->postJson("/api/library/copies/{$copy->id}/return")
            ->assertOk()->assertJsonPath('book.copies.0.loan', null);

        $this->actingAs($user)->postJson("/api/library/copies/{$copy->id}/return")->assertStatus(409);

        // history is kept
        $this->assertSame(1, Loan::where('copy_id', $copy->id)->whereNotNull('returned_at')->count());
        $this->assertSame($user->id, Loan::where('copy_id', $copy->id)->first()->returned_by);
    }

    public function test_due_date_and_renewal(): void
    {
        $copy = $this->book()->copies[0];
        $member = Member::create(['first_name' => 'Ann']);
        $service = app(LibraryService::class);

        $loan = $service->borrow($copy, $member);
        $this->assertEquals(LibraryService::LOAN_DAYS, now()->startOfDay()->diffInDays($loan->due_at->startOfDay()));

        $renewed = $service->renew($loan);
        $this->assertSame(1, $renewed->renewals);
        $this->assertTrue($renewed->due_at->gt($loan->borrowed_at->addDays(LibraryService::LOAN_DAYS)));
    }

    public function test_lost_copy_cannot_be_borrowed(): void
    {
        $copy = $this->book()->copies[0];
        $copy->update(['status' => Copy::STATUS_LOST]);

        $this->actingAs($this->user())
            ->postJson("/api/library/copies/{$copy->id}/borrow", ['first_name' => 'Ann'])
            ->assertStatus(409);
    }

    public function test_new_member_is_found_by_name_not_duplicated(): void
    {
        $user = $this->user();
        $payload = ['first_name' => '三', 'last_name' => '张', 'email' => 'a@b.de'];

        $id = $this->actingAs($user)->postJson('/api/library/members', $payload)->json('member.id');
        $again = $this->actingAs($user)->postJson('/api/library/members', $payload + ['phone' => '123'])->json('member.id');

        $this->assertSame($id, $again);
        $this->assertSame('123', Member::find($id)->phone);
    }

    public function test_only_managers_can_save_books_and_codes_are_generated(): void
    {
        $data = ['title' => 'Neu', 'isbn' => '9780306406171', 'quantity' => 2];

        $this->actingAs($this->user())->postJson('/api/library/books', $data)->assertForbidden();

        $manager = $this->user(['library', 'library_management']);
        $first = $this->actingAs($manager)->postJson('/api/library/books', $data)->assertOk();
        $code = $first->json('book.code');
        $this->assertStringStartsWith('FMCD', $code);
        $this->assertCount(2, $first->json('book.copies'));
        $this->assertSame($code . '.2', $first->json('book.copies.1.code'));

        // same ISBN: updates the book and tops up the copies, no duplicate
        $second = $this->actingAs($manager)->postJson('/api/library/books', ['quantity' => 3, 'title' => 'Neu v2'] + $data)->assertOk();
        $this->assertSame($first->json('book.id'), $second->json('book.id'));
        $this->assertCount(3, $second->json('book.copies'));
        $this->assertSame('Neu v2', $second->json('book.title'));
    }

    public function test_manager_can_add_one_more_copy_and_numbers_never_repeat(): void
    {
        $book = $this->book(2);
        $book->copies[0]->delete(); // a gap: copy 1 was removed, the next number must still be 3

        $this->actingAs($this->user())->postJson("/api/library/books/{$book->id}/copies")->assertForbidden();

        $manager = $this->user(['library', 'library_management']);
        $res = $this->actingAs($manager)->postJson("/api/library/books/{$book->id}/copies")->assertOk();

        $this->assertSame($book->code . '.3', $res->json('book.copies.1.code'));
        $this->assertCount(2, $res->json('book.copies'));
    }

    public function test_metadata_reports_existing_book_without_web_lookup(): void
    {
        $book = $this->book(1);
        $manager = $this->user(['library', 'library_management']);

        // Http is faked to fail loudly: an existing book must not trigger a lookup
        \Illuminate\Support\Facades\Http::fake(fn () => throw new \RuntimeException('no web lookup expected'));

        $this->actingAs($manager)->getJson('/api/library/metadata?isbn=' . $book->isbn)
            ->assertOk()->assertJsonPath('existing.id', $book->id)->assertJsonPath('metadata', null);
    }

    public function test_manager_can_edit_a_book_and_renaming_the_code_renames_the_copies(): void
    {
        $book = $this->book(2, ['isbn' => null]);
        $other = $this->book(1, ['isbn' => '9780306406157']);
        $payload = ['title' => 'Neuer Titel', 'author' => '  Autor ', 'isbn' => '978-0-306-40616-4', 'code' => $book->code];

        $this->actingAs($this->user())->patchJson("/api/library/books/{$book->id}", $payload)->assertForbidden();

        $manager = $this->user(['library', 'library_management']);
        $res = $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", $payload)->assertOk();

        $this->assertSame('Neuer Titel', $res->json('book.title'));
        $this->assertSame('Autor', $res->json('book.author'));
        $this->assertSame('9780306406164', $res->json('book.isbn'));
        $this->assertSame($book->code, $res->json('book.code'));
        $this->assertCount(2, $res->json('book.copies'));

        // renaming the code renames the copies, and a loan stays attached to its copy
        $loan = app(LibraryService::class)->borrow($book->copies[1], Member::create(['first_name' => 'Ann']));
        $renamed = $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => 'Neuer Titel', 'code' => ' NEW-1 '])->assertOk();
        $this->assertSame('NEW-1', $renamed->json('book.code'));
        $this->assertSame(['NEW-1.1', 'NEW-1.2'], collect($renamed->json('book.copies'))->pluck('code')->all());
        $this->assertSame($loan->id, $renamed->json('book.copies.1.loan.id'));
        $this->actingAs($manager)->getJson('/api/library/lookup?code=NEW-1.2')->assertJsonPath('copyId', $book->copies[1]->id);
        $this->actingAs($manager)->getJson('/api/library/lookup?code=' . $book->copies[1]->code)->assertJsonPath('found', false);

        // a code of another book is refused, and so is an empty one
        $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => 'X', 'code' => $other->code])->assertStatus(422);
        $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => 'X', 'code' => ''])->assertStatus(422);
        $this->assertSame('NEW-1', $book->fresh()->code, 'a refused edit changes nothing');

        // emptied optional fields become null, ISBN of another book / invalid ISBN are refused
        $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => 'X', 'code' => 'NEW-1', 'author' => '', 'isbn' => ''])
            ->assertOk()->assertJsonPath('book.author', null)->assertJsonPath('book.isbn', null);
        $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => 'X', 'code' => 'NEW-1', 'isbn' => $other->isbn])->assertStatus(422);
        $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => 'X', 'code' => 'NEW-1', 'isbn' => '12'])->assertStatus(422);
        $this->actingAs($manager)->patchJson("/api/library/books/{$book->id}", ['title' => '', 'code' => 'NEW-1'])->assertStatus(422);

        // keeping its own ISBN is fine
        $this->actingAs($manager)->patchJson("/api/library/books/{$other->id}", ['title' => 'Same', 'code' => $other->code, 'isbn' => $other->isbn])->assertOk();
    }

    public function test_invalid_isbn_is_rejected(): void
    {
        $manager = $this->user(['library', 'library_management']);
        $this->actingAs($manager)->postJson('/api/library/books', ['title' => 'X', 'isbn' => '123', 'quantity' => 1])
            ->assertStatus(422);
    }

    public function test_book_list_filters_and_searches(): void
    {
        $free = $this->book(1, ['title' => 'Freies Zebra', 'isbn' => null]);
        $out = $this->book(1, ['title' => 'Verliehenes Zebra', 'isbn' => null]);
        app(LibraryService::class)->borrow($out->copies[0], Member::create(['first_name' => 'Ann']));
        $user = $this->user();

        $ids = fn ($q) => collect($this->actingAs($user)->getJson('/api/library/books?' . $q)->json('books'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$free->id, $out->id], $ids('search=Zebra'));
        $this->assertSame([$free->id], $ids('search=Zebra&filter=available'));
        $this->assertSame([$out->id], $ids('search=Zebra&filter=borrowed'));
    }

    public function test_overdue_flag(): void
    {
        $copy = $this->book()->copies[0];
        $loan = app(LibraryService::class)->borrow($copy, Member::create(['first_name' => 'Ann']));

        $this->assertFalse($loan->fresh()->is_overdue);
        $loan->update(['due_at' => now()->subDay()]);
        $this->assertTrue($loan->fresh()->is_overdue);
        $loan->update(['due_at' => now()->startOfDay()]);
        $this->assertFalse($loan->fresh()->is_overdue, 'due today is not overdue yet');
    }
}
