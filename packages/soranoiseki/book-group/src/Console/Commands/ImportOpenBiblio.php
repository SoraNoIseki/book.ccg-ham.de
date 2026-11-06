<?php

namespace Soranoiseki\BookGroup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Copy;
use Soranoiseki\BookGroup\Models\Library\Loan;
use Soranoiseki\BookGroup\Models\Library\Member;
use Soranoiseki\BookGroup\Services\LibraryService;

/**
 * Copies books, copies, members and the current loans from the old openbiblio database
 * (connection "openbiblio") into the library_* tables. Safe to run repeatedly: rows are matched
 * by their legacy id, so a second run updates instead of duplicating.
 */
class ImportOpenBiblio extends Command
{
    protected $signature = 'library:import-openbiblio {--dry-run : Read and report, but write nothing}';

    protected $description = 'Import books, copies, members and open loans from the old openbiblio database';

    /** @var array<string,int> */
    private array $stats = [];

    /** @var string[] */
    private array $warnings = [];

    public function handle(LibraryService $library): int
    {
        $legacy = DB::connection('openbiblio');

        try {
            $legacy->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to the openbiblio database: ' . $e->getMessage());

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? 'Dry run: nothing will be written.' : 'Importing from openbiblio ...');

        DB::beginTransaction();

        try {
            $members = $this->importMembers($legacy);
            $books = $this->importBooks($legacy, $library);
            $copies = $this->importCopies($legacy, $books);
            $this->importLoans($legacy, $copies, $members);

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed, nothing was written: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->table(['What', 'Count'], collect($this->stats)->map(fn ($n, $k) => [$k, $n])->values()->all());
        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }

    /** @return array<int,int> legacy mbrid => new member id */
    private function importMembers($legacy): array
    {
        $map = [];

        foreach ($legacy->table('member')->orderBy('mbrid')->get() as $row) {
            $first = trim((string) $row->first_name);
            $last = trim((string) $row->last_name);
            $end = $row->mbrshipend ?? null;

            $member = Member::updateOrCreate(['legacy_id' => $row->mbrid], [
                'card_no' => $this->blank($row->barcode_nmbr),
                'first_name' => $first !== '' ? $first : $last,
                'last_name' => $first !== '' ? $last : '',
                'email' => $this->blank($row->email),
                'phone' => $this->blank($row->home_phone) ?? $this->blank($row->work_phone ?? null),
                // openbiblio used 2070-01-01 as "never expires"
                'membership_ends_at' => $end && substr((string) $end, 0, 4) < '2070' ? $end : null,
            ]);

            $map[$row->mbrid] = $member->id;
        }

        $this->stats['members'] = count($map);

        return $map;
    }

    /** @return array<int,int> legacy bibid => new book id */
    private function importBooks($legacy, LibraryService $library): array
    {
        $map = [];

        foreach ($legacy->table('biblio')->orderBy('bibid')->get() as $row) {
            $isbn = $library->normalizeIsbn($row->call_nmbr2);
            $category = $this->blank($row->topic1);

            // The old add-book form stored the ISBN in the category field as well.
            if ($category && $library->normalizeIsbn($category)) {
                $category = null;
            }

            $code = trim((string) $row->call_nmbr1);
            if ($code === '') {
                $code = 'LEGACY' . $row->bibid;
                $this->warnings[] = "Book #{$row->bibid} \"{$row->title}\" has no call number, using {$code}.";
            }

            $book = Book::updateOrCreate(['legacy_id' => $row->bibid], [
                'code' => $code,
                'isbn' => $isbn,
                'title' => trim((string) $row->title),
                'subtitle' => $this->blank($row->title_remainder),
                'author' => $this->blank($row->author),
                'publisher' => $this->blank($row->responsibility_stmt),
                'category' => $category,
                'is_visible' => ($row->opac_flg ?? 'Y') !== 'N',
            ]);

            $map[$row->bibid] = $book->id;
        }

        $this->stats['books'] = count($map);

        return $map;
    }

    /** @return array<string,int> "bibid:copyid" => new copy id */
    private function importCopies($legacy, array $books): array
    {
        $map = [];
        $numbers = [];
        $lost = 0;

        foreach ($legacy->table('biblio_copy')->orderBy('bibid')->orderBy('copyid')->get() as $row) {
            if (!isset($books[$row->bibid])) {
                $this->warnings[] = "Copy {$row->bibid}/{$row->copyid} belongs to an unknown book, skipped.";
                continue;
            }

            $bookId = $books[$row->bibid];
            $existing = Copy::where('book_id', $bookId)->where('legacy_id', $row->copyid)->first();
            $number = $existing?->number ?? (($numbers[$bookId] ?? (int) Copy::where('book_id', $bookId)->max('number')) + 1);
            $numbers[$bookId] = max($numbers[$bookId] ?? 0, $number);

            // openbiblio status: in, out, crt (shelving cart), ln (on loan), dis (display), hld (hold), lst (lost) ...
            $status = in_array($row->status_cd, ['lst', 'lost'], true) ? Copy::STATUS_LOST : Copy::STATUS_ACTIVE;
            $lost += $status === Copy::STATUS_LOST ? 1 : 0;

            $bookCode = Book::whereKey($bookId)->value('code');
            $copy = Copy::updateOrCreate(['book_id' => $bookId, 'legacy_id' => $row->copyid], [
                'number' => $number,
                'code' => $bookCode . '.' . $number,
                'status' => $status,
            ]);

            $map[$row->bibid . ':' . $row->copyid] = $copy->id;
        }

        $this->stats['copies'] = count($map);
        $this->stats['copies marked lost'] = $lost;

        return $map;
    }

    private function importLoans($legacy, array $copies, array $members): void
    {
        $created = 0;

        foreach ($legacy->table('biblio_copy')->where('status_cd', 'out')->get() as $row) {
            $copyId = $copies[$row->bibid . ':' . $row->copyid] ?? null;
            $memberId = $members[$row->mbrid] ?? null;

            if (!$copyId || !$memberId) {
                $this->warnings[] = "Copy {$row->bibid}/{$row->copyid} is marked as out but has no valid member (mbrid={$row->mbrid}); it will show as available.";
                continue;
            }

            if (Loan::where('copy_id', $copyId)->whereNull('returned_at')->exists()) {
                continue; // already imported by an earlier run
            }

            $borrowed = $row->status_begin_dt ?: now();
            Loan::create([
                'copy_id' => $copyId,
                'member_id' => $memberId,
                'borrowed_at' => $borrowed,
                'due_at' => $row->due_back_dt ?: \Carbon\Carbon::parse($borrowed)->addDays(LibraryService::LOAN_DAYS),
                'renewals' => (int) ($row->renewal_count ?? 0),
            ]);
            $created++;
        }

        $this->stats['open loans'] = $created;
    }

    private function blank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
