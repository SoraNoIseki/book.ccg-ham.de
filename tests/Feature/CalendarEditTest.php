<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Detail;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Tests\TestCase;

/** Editing single imported rows (events and monthly verses) of a calendar. */
class CalendarEditTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create(['firstname' => 'Test', 'lastname' => 'User', 'enabled' => true]);
        $role = Role::firstOrCreate(['internal_name' => 'calendar'], ['name' => 'calendar']);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function calendar(?int $year = null): Calendar
    {
        $calendar = new Calendar();
        $calendar->year = $year ?? (int) date('Y') + 1;
        $calendar->save();

        return $calendar;
    }

    private function event(Calendar $c, string $date, string $name = 'Alt'): Event
    {
        return Event::create(['year' => $c->year, 'date' => $date, 'name' => $name, 'type' => 'yearly', 'calendar_id' => $c->id]);
    }

    public function test_event_can_be_edited_and_lines_are_stored_with_pipes(): void
    {
        $c = $this->calendar();
        $e = $this->event($c, "{$c->year}-01-18");

        $this->actingAs($this->user())
            ->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-01-19", 'name' => "会友大会\n\n登录事选举\r\n"])
            ->assertOk()
            ->assertJsonPath('events.0.date', "{$c->year}-01-19")
            ->assertJsonPath('events.0.name', '会友大会|登录事选举');
    }

    public function test_event_date_must_be_in_the_year_and_free(): void
    {
        $c = $this->calendar();
        $e = $this->event($c, "{$c->year}-01-18");
        $this->event($c, "{$c->year}-02-01", 'Andere');
        $user = $this->user();

        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => ($c->year + 2) . '-01-01', 'name' => 'x'])
            ->assertStatus(422);
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-02-01", 'name' => 'x'])
            ->assertStatus(422);
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-01-18", 'name' => " \n "])
            ->assertStatus(422);
        // Only days that show up in the calendar: the padding days of the January / December weeks
        $min = \Carbon\Carbon::create($c->year, 1, 1)->startOfWeek();
        $max = \Carbon\Carbon::create($c->year, 12, 31)->endOfWeek();
        foreach ([$min->copy()->subDay(), $max->copy()->addDay(), \Carbon\Carbon::create($c->year - 1, 12, 12)] as $outside) {
            $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => $outside->format('Y-m-d'), 'name' => 'x'])
                ->assertStatus(422);
        }
        foreach ([$min, $max] as $inside) {
            $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => $inside->format('Y-m-d'), 'name' => 'x'])
                ->assertOk();
        }
    }

    public function test_event_of_another_calendar_is_not_found(): void
    {
        $c = $this->calendar();
        $other = $this->calendar($c->year + 1);
        $e = $this->event($other, "{$other->year}-03-01");

        $this->actingAs($this->user())->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-03-01", 'name' => 'x'])
            ->assertNotFound();
    }

    public function test_bible_text_is_updated_or_created(): void
    {
        $c = $this->calendar();
        $user = $this->user();

        // Month without a verse yet -> created
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/3", ['text' => "Zeile 1\nZeile 2", 'source' => ' Joh 3,16 '])
            ->assertOk()->assertJsonPath('details.0.bible_text', 'Zeile 1|Zeile 2')->assertJsonPath('details.0.bible_source', 'Joh 3,16');

        // Existing month -> updated, no duplicate
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/3", ['text' => 'Neu', 'source' => ''])->assertOk();
        $this->assertSame(1, Detail::where('calendar_id', $c->id)->count());
        $this->assertSame('Neu', Detail::where('calendar_id', $c->id)->first()->bible_text);

        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/13", ['text' => 'x'])->assertNotFound();
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/4", ['text' => '  '])->assertStatus(422);
    }

    public function test_bible_texts_can_be_rearranged(): void
    {
        $c = $this->calendar();
        $user = $this->user();
        foreach ([1 => 'Jan', 3 => 'Mar', 12 => 'Dez'] as $m => $t) {
            Detail::create(['calendar_id' => $c->id, 'year' => $c->year, 'month' => $m, 'bible_text' => $t, 'bible_source' => "S$m"]);
        }
        $texts = fn () => Detail::where('calendar_id', $c->id)->orderBy('month')->pluck('bible_text', 'month')->all();

        // Swap January and March
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/order", ['order' => [3, 2, 1, 4, 5, 6, 7, 8, 9, 10, 11, 12]])
            ->assertOk();
        $this->assertSame([1 => 'Mar', 3 => 'Jan', 12 => 'Dez'], $texts());

        // Move December to after September (10, 11, 12 shift down); the unset months move along
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/order", ['order' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 12, 10, 11]])
            ->assertOk();
        $this->assertSame([1 => 'Mar', 3 => 'Jan', 10 => 'Dez'], $texts());
        $this->assertSame('S12', Detail::where('calendar_id', $c->id)->where('month', 10)->value('bible_source'));

        // Not a permutation of 1..12
        foreach ([[1, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], [1, 2, 3], [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13]] as $bad) {
            $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/order", ['order' => $bad])->assertStatus(422);
        }
        $this->assertSame([1 => 'Mar', 3 => 'Jan', 10 => 'Dez'], $texts());
    }

    public function test_image_crop_is_saved_reset_and_validated(): void
    {
        Storage::fake('public');
        $c = $this->calendar();
        $user = $this->user();
        $url = "/api/calendar/{$c->id}/images/03/crop";

        // No picture yet
        $this->actingAs($user)->putJson($url, ['x' => 0.3, 'y' => 0.6, 'zoom' => 1.5])->assertStatus(422);

        Storage::disk('public')->put("images/calendar/{$c->year}/03.jpg", 'x');

        $this->actingAs($user)->putJson($url, ['x' => 0.3, 'y' => 0.6, 'zoom' => 1.5])
            ->assertOk()->assertJsonPath('crops.03.x', 0.3)->assertJsonPath('crops.03.zoom', 1.5);
        $this->actingAs($user)->getJson("/api/calendar?year={$c->year}")
            ->assertJsonPath('crops.03.y', 0.6)->assertJsonPath('headerPreview.ratio', 0.5);

        foreach ([['x' => 1.2, 'y' => 0.5, 'zoom' => 1], ['x' => 0.5, 'y' => 0.5, 'zoom' => 0.5], ['x' => 0.5, 'y' => 0.5, 'zoom' => 5], ['x' => 0.5]] as $bad) {
            $this->actingAs($user)->putJson($url, $bad)->assertStatus(422);
        }
        // Only the monthly photos have a range
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/cover/crop", ['x' => 0.3, 'y' => 0.6, 'zoom' => 1])->assertNotFound();

        // Centred without zoom is the default and is stored as nothing
        $this->actingAs($user)->putJson($url, ['x' => 0.5, 'y' => 0.5, 'zoom' => 1])->assertOk();
        $this->assertNull($c->fresh()->image_crops);
    }

    public function test_new_upload_or_delete_forgets_the_crop(): void
    {
        Storage::fake('public');
        $c = $this->calendar();
        $user = $this->user();
        Storage::disk('public')->put("images/calendar/{$c->year}/03.jpg", 'x');
        Storage::disk('public')->put("images/calendar/{$c->year}/04.jpg", 'x');
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/03/crop", ['x' => 0.2, 'y' => 0.5, 'zoom' => 2])->assertOk();
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/04/crop", ['x' => 0.7, 'y' => 0.5, 'zoom' => 2])->assertOk();

        $this->actingAs($user)->post("/api/calendar/{$c->id}/images/03", ['file' => UploadedFile::fake()->image('new.jpg', 100, 80)], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonMissingPath('crops.03')->assertJsonPath('crops.04.x', 0.7);

        $this->actingAs($user)->deleteJson("/api/calendar/{$c->id}/images/04")->assertOk()->assertJsonCount(0, 'crops');
    }

    public function test_monthly_images_can_be_rearranged_together_with_their_crops(): void
    {
        Storage::fake('public');
        $c = $this->calendar();
        $user = $this->user();
        $dir = "images/calendar/{$c->year}";
        Storage::disk('public')->put("$dir/01.jpg", 'jan');
        Storage::disk('public')->put("$dir/03.png", 'mar');
        Storage::disk('public')->put("$dir/cover.jpg", 'cover');
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/03/crop", ['x' => 0.2, 'y' => 0.5, 'zoom' => 2])->assertOk();

        // Swap January and March (the photo moves together with its range)
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/order", ['order' => [3, 2, 1, 4, 5, 6, 7, 8, 9, 10, 11, 12]])
            ->assertOk()->assertJsonPath('crops.01.x', 0.2)->assertJsonMissingPath('crops.03');
        $disk = Storage::disk('public');
        $this->assertSame('mar', $disk->get("$dir/01.png"));
        $this->assertSame('jan', $disk->get("$dir/03.jpg"));
        $this->assertSame('cover', $disk->get("$dir/cover.jpg"));
        $this->assertFalse($disk->exists("$dir/01.jpg"));
        $this->assertFalse($disk->exists("$dir/03.png"));

        // Move a photo into an empty month
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/order", ['order' => [2, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]])->assertOk();
        $this->assertSame('mar', $disk->get("$dir/02.png"));
        $this->assertFalse($disk->exists("$dir/01.png"));
        $this->assertCount(3, $disk->files($dir));

        // Not a permutation of 1..12
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/images/order", ['order' => [1, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]])->assertStatus(422);
    }

    public function test_uploaded_photos_are_scaled_down_and_converted_to_jpg(): void
    {
        Storage::fake('public');
        $c = $this->calendar();
        $user = $this->user();
        $dir = "images/calendar/{$c->year}";
        $disk = Storage::disk('public');
        $post = fn (string $slot, UploadedFile $file) => $this->actingAs($user)->post("/api/calendar/{$c->id}/images/$slot", ['file' => $file], ['Accept' => 'application/json'])->assertOk();

        // A big PNG photo becomes a JPEG of at most 3508 px width
        $post('05', UploadedFile::fake()->image('big.png', 4200, 2800));
        $this->assertTrue($disk->exists("$dir/05.jpg"));
        $this->assertFalse($disk->exists("$dir/05.png"));
        $this->assertSame([3508, 2339, IMAGETYPE_JPEG], array_slice(getimagesizefromstring($disk->get("$dir/05.jpg")), 0, 3));

        // A small photo keeps its size (never scaled up), but is converted
        $post('06', UploadedFile::fake()->image('small.png', 800, 600));
        $this->assertSame([800, 600], array_slice(getimagesizefromstring($disk->get("$dir/06.jpg")), 0, 2));

        // Cover, overlay and footer are stored as uploaded
        $big = UploadedFile::fake()->image('o.png', 4000, 2000);
        $post('overlay', $big);
        $post('cover', UploadedFile::fake()->image('c.png', 4000, 5000));
        $this->assertSame([4000, 2000, IMAGETYPE_PNG], array_slice(getimagesizefromstring($disk->get("$dir/overlay.png")), 0, 3));
        $this->assertSame([4000, 5000, IMAGETYPE_PNG], array_slice(getimagesizefromstring($disk->get("$dir/cover.png")), 0, 3));
    }

    public function test_previews_are_made_on_first_use_and_cached(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $c = $this->calendar();
        $user = $this->user();
        $dir = "images/calendar/{$c->year}";
        Storage::disk('public')->put("$dir/05.jpg", UploadedFile::fake()->image('a.jpg', 3000, 2000)->get());
        Storage::disk('public')->put("$dir/06.jpg", UploadedFile::fake()->image('b.jpg', 600, 400)->get());
        Storage::disk('public')->put("$dir/overlay.png", UploadedFile::fake()->image('o.png', 3000, 1500)->get());
        $get = fn (string $url) => $this->actingAs($user)->get(preg_replace('/\?.*/', '', $url));

        $info = $this->actingAs($user)->getJson("/api/calendar?year={$c->year}")->json('images');
        $this->assertStringStartsWith("/api/calendar/{$c->id}/images/05/preview/thumb?v=", $info['05']['thumb']);
        $this->assertNull($info['07']['thumb']);

        $thumb = $get($info['05']['thumb'])->assertOk();
        $this->assertSame([1000, 667, IMAGETYPE_JPEG], array_slice(getimagesizefromstring($thumb->baseResponse->getFile()->getContent()), 0, 3));
        $view = $get($info['05']['preview'])->assertOk();
        $this->assertSame(2000, getimagesizefromstring($view->baseResponse->getFile()->getContent())[0]);
        $this->assertCount(2, Storage::disk('local')->files("calendar-previews/{$c->year}"));

        // A small image is served as it is; the overlay keeps its format
        $this->assertSame(600, getimagesizefromstring($get($info['06']['thumb'])->baseResponse->getFile()->getContent())[0]);
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($get($info['overlay']['thumb'])->baseResponse->getFile()->getContent())[2]);

        // A new upload replaces the copies
        $this->actingAs($user)->post("/api/calendar/{$c->id}/images/05", ['file' => UploadedFile::fake()->image('n.jpg', 3000, 3000)], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame([], array_values(preg_grep('/05-/', Storage::disk('local')->files("calendar-previews/{$c->year}"))));

        $this->actingAs($user)->get("/api/calendar/{$c->id}/images/05/preview/huge")->assertNotFound();
        $this->actingAs($user)->get("/api/calendar/{$c->id}/images/09/preview/thumb")->assertNotFound();
    }

    public function test_command_optimizes_existing_images(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $png = UploadedFile::fake()->image('a.png', 4200, 2800)->get();
        $jpgBig = UploadedFile::fake()->image('b.jpg', 4000, 1000)->get();
        $jpgOk = UploadedFile::fake()->image('c.jpg', 1000, 500)->get();
        $disk->put('images/calendar/2031/01.png', $png);
        $disk->put('images/calendar/2031/02.jpg', $jpgBig);
        $disk->put('images/calendar/2031/03.jpg', $jpgOk);
        $disk->put('images/calendar/2032/04.png', $png);
        $disk->put('images/calendar/2032/cover.png', $png);

        // Dry run: reports, but changes nothing
        $this->artisan('book:optimize-calendar-images', ['--dry-run' => true])->assertSuccessful();
        $this->assertTrue($disk->exists('images/calendar/2031/01.png'));
        $this->assertSame($jpgBig, $disk->get('images/calendar/2031/02.jpg'));

        $this->artisan('book:optimize-calendar-images', ['year' => 2031])->assertSuccessful();
        $this->assertFalse($disk->exists('images/calendar/2031/01.png'));
        $this->assertSame([3508, 2339, IMAGETYPE_JPEG], array_slice(getimagesizefromstring($disk->get('images/calendar/2031/01.jpg')), 0, 3));
        $this->assertSame(3508, getimagesizefromstring($disk->get('images/calendar/2031/02.jpg'))[0]);
        $this->assertSame($jpgOk, $disk->get('images/calendar/2031/03.jpg')); // already fine: untouched
        $this->assertTrue($disk->exists('images/calendar/2032/04.png')); // other year not asked for

        $this->artisan('book:optimize-calendar-images')->assertSuccessful();
        $this->assertTrue($disk->exists('images/calendar/2032/04.jpg'));
        $this->assertFalse($disk->exists('images/calendar/2032/04.png'));
        $this->assertTrue($disk->exists('images/calendar/2032/cover.png')); // the cover is not touched
    }

    public function test_history_calendars_and_users_without_the_role_cannot_edit(): void
    {
        $old = $this->calendar((int) date('Y') - 1);
        $e = $this->event($old, "{$old->year}-01-18");

        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/events/{$e->id}", ['date' => "{$old->year}-01-19", 'name' => 'x'])
            ->assertForbidden();
        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/bible-texts/1", ['text' => 'x'])->assertForbidden();
        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/bible-texts/order", ['order' => range(1, 12)])->assertForbidden();
        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/images/03/crop", ['x' => 0.3, 'y' => 0.5, 'zoom' => 1])->assertForbidden();

        $c = $this->calendar();
        $nobody = User::factory()->create(['firstname' => 'No', 'lastname' => 'Body', 'enabled' => true]);
        $this->actingAs($nobody)->putJson("/api/calendar/{$c->id}/bible-texts/1", ['text' => 'x'])->assertUnauthorized();
    }
}
