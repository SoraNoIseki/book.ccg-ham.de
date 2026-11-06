<?php

namespace Soranoiseki\BookGroup\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Copy;
use Soranoiseki\BookGroup\Models\Library\Loan;
use Soranoiseki\BookGroup\Models\Library\Member;

/**
 * Borrowing rules and catalogue changes of the library. Controllers stay thin and call this.
 * Rule violations are thrown as DomainException with a message that can be shown to the user.
 */
class LibraryService
{
    public const LOAN_DAYS = 28;

    /** Prefix of catalogue numbers generated for books bought by the church. */
    public const CODE_PREFIX = 'FMCD';

    // ---- Loans -------------------------------------------------------------------------------

    public function borrow(Copy $copy, Member $member, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($copy, $member, $userId) {
            // Lock the copy so two phones scanning the same book cannot both borrow it.
            $copy = Copy::whereKey($copy->id)->lockForUpdate()->firstOrFail();

            if ($copy->status !== Copy::STATUS_ACTIVE) {
                throw new DomainException('这本书目前不能借出（已遗失或已下架）。');
            }
            if ($copy->activeLoan()->exists()) {
                throw new DomainException('这本书已经被借出了。');
            }

            return Loan::create([
                'copy_id' => $copy->id,
                'member_id' => $member->id,
                'borrowed_at' => now(),
                'due_at' => now()->addDays(self::LOAN_DAYS),
                'renewals' => 0,
                'borrowed_by' => $userId,
            ]);
        });
    }

    public function return(Copy $copy, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($copy, $userId) {
            $copy = Copy::whereKey($copy->id)->lockForUpdate()->firstOrFail();
            $loan = $copy->activeLoan()->first();

            if (!$loan) {
                throw new DomainException('这本书没有借出，不需要归还。');
            }

            $loan->update(['returned_at' => now(), 'returned_by' => $userId]);

            return $loan;
        });
    }

    public function renew(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            $loan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->returned_at) {
                throw new DomainException('这本书已经归还，不能续借。');
            }

            // Renewing counts from today, or from the old due date if that is still ahead.
            $from = $loan->due_at->isFuture() ? $loan->due_at : now();
            $loan->update([
                'due_at' => $from->copy()->addDays(self::LOAN_DAYS),
                'renewals' => $loan->renewals + 1,
            ]);

            return $loan;
        });
    }

    // ---- Members -----------------------------------------------------------------------------

    /** Find a member by name (and update contact details) or create a new one. */
    public function findOrCreateMember(array $data): Member
    {
        $first = trim($data['first_name']);
        $last = trim($data['last_name'] ?? '');

        $member = Member::where('first_name', $first)->where('last_name', $last)->first();

        if ($member) {
            $member->fill(array_filter([
                'email' => isset($data['email']) ? trim($data['email']) : null,
                'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            ], fn ($v) => $v !== null && $v !== ''))->save();

            return $member;
        }

        return Member::create([
            'first_name' => $first,
            'last_name' => $last,
            'email' => isset($data['email']) ? trim($data['email']) : null,
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
        ]);
    }

    // ---- Catalogue ---------------------------------------------------------------------------

    /**
     * Create the book (or update it when the ISBN / code already exists) and make sure it has at
     * least `$quantity` copies. Never removes copies.
     */
    public function saveBook(array $data, int $quantity): Book
    {
        return DB::transaction(function () use ($data, $quantity) {
            $isbn = $this->normalizeIsbn($data['isbn'] ?? null);
            $code = trim((string) ($data['code'] ?? ''));

            $book = null;
            if ($code !== '') {
                $book = Book::where('code', $code)->first();
            }
            if (!$book && $isbn) {
                $book = Book::where('isbn', $isbn)->first();
            }

            $attributes = [
                'isbn' => $isbn,
                'title' => trim($data['title']),
                'subtitle' => $this->blankToNull($data['subtitle'] ?? null),
                'author' => $this->blankToNull($data['author'] ?? null),
                'publisher' => $this->blankToNull($data['publisher'] ?? null),
                'category' => $this->blankToNull($data['category'] ?? null),
                'cover_url' => $this->blankToNull($data['cover_url'] ?? null),
            ];

            if ($book) {
                $book->fill($attributes)->save();
            } else {
                $book = Book::create($attributes + ['code' => $code !== '' ? $code : $this->nextCode()]);
            }

            $this->ensureCopies($book, $quantity);

            return $book;
        });
    }

    /** Add one more physical copy to an existing book. */
    public function addCopy(Book $book): Copy
    {
        return DB::transaction(function () use ($book) {
            // Lock the book so two managers adding at once get different numbers.
            $book = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
            $number = (int) $book->copies()->max('number') + 1;

            return $book->copies()->create(['number' => $number, 'code' => $book->code . '.' . $number]);
        });
    }

    public function ensureCopies(Book $book, int $quantity): void
    {
        $existing = (int) $book->copies()->max('number');

        for ($n = $existing + 1; $n <= $quantity; $n++) {
            $book->copies()->create(['number' => $n, 'code' => $book->code . '.' . $n]);
        }
    }

    /** Next free FMCD#### catalogue number. */
    public function nextCode(): string
    {
        $last = Book::where('code', 'like', self::CODE_PREFIX . '%')
            ->orderByRaw('LENGTH(code) DESC, code DESC')
            ->value('code');

        $number = $last ? (int) substr($last, strlen(self::CODE_PREFIX)) : 0;

        return self::CODE_PREFIX . str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve something that was scanned or typed: a copy label, a catalogue number or an ISBN.
     * Returns ['book' => Book, 'copy' => ?Copy], or null when nothing matches.
     */
    public function lookup(string $input): ?array
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        if ($copy = Copy::where('code', $input)->first()) {
            return ['book' => $copy->book, 'copy' => $copy];
        }

        $isbn = $this->normalizeIsbn($input);
        $book = Book::where('code', $input)
            ->when($isbn, fn ($q) => $q->orWhere('isbn', $isbn))
            ->first();

        return $book ? ['book' => $book, 'copy' => null] : null;
    }

    /** ISBN without hyphens or spaces; null when it is not a 10 or 13 character ISBN. */
    public function normalizeIsbn(?string $value): ?string
    {
        $isbn = strtoupper(preg_replace('/[\s-]+/', '', (string) $value));

        return preg_match('/^(\d{13}|\d{9}[\dX])$/', $isbn) ? $isbn : null;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
