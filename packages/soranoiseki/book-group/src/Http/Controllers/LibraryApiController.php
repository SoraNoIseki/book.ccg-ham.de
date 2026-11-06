<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Soranoiseki\BookGroup\Imports\ImportBooks;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Copy;
use Soranoiseki\BookGroup\Models\Library\Loan;
use Soranoiseki\BookGroup\Models\Library\Member;
use Soranoiseki\BookGroup\Services\BookMetadataService;
use Soranoiseki\BookGroup\Services\LibraryService;
use Soranoiseki\Core\Controllers\Controller;

/**
 * JSON API for the library page (Vue). Everyone with the "library" role may look things up and
 * lend / return books; changing the catalogue needs "library_management" (or "admin").
 */
class LibraryApiController extends Controller
{
    public const MANAGE_ROLE = 'library_management';

    public function __construct(private LibraryService $library, private BookMetadataService $metadata)
    {
    }

    /** GET /api/library/summary */
    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'canManage' => $this->canManage($request),
            'loanDays' => LibraryService::LOAN_DAYS,
            'books' => Book::count(),
            'copies' => Copy::count(),
            'loans' => Loan::active()->count(),
            'overdue' => Loan::active()->where('due_at', '<', now()->startOfDay())->count(),
        ]);
    }

    /** GET /api/library/books?search=&filter=all|available|borrowed&page= */
    public function books(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $filter = $request->query('filter', 'all');

        $books = Book::with(['copies.activeLoan.member'])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('subtitle', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('author', 'like', $like)
                    ->orWhere('isbn', 'like', $like));
            })
            ->when($filter === 'available', fn ($q) => $q->whereHas('copies', fn ($c) => $c
                ->where('status', Copy::STATUS_ACTIVE)->whereDoesntHave('activeLoan')))
            ->when($filter === 'borrowed', fn ($q) => $q->whereHas('copies.activeLoan'))
            ->orderByRaw('LENGTH(code), code')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'books' => $books->getCollection()->map(fn ($b) => $this->bookPayload($b))->values(),
            'page' => $books->currentPage(),
            'lastPage' => $books->lastPage(),
            'total' => $books->total(),
        ]);
    }

    /** GET /api/library/lookup?code=  (scanned copy label, catalogue number or ISBN) */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));
        $found = $this->library->lookup($code);

        if (!$found) {
            return response()->json([
                'success' => true,
                'found' => false,
                'code' => $code,
                'isbn' => $this->library->normalizeIsbn($code),
            ]);
        }

        $book = $found['book']->load('copies.activeLoan.member');

        return response()->json([
            'success' => true,
            'found' => true,
            'book' => $this->bookPayload($book),
            'copyId' => $found['copy']?->id,
        ]);
    }

    /** GET /api/library/metadata?isbn=  (manager) Title, author ... of an ISBN, plus the existing book */
    public function metadata(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $isbn = $this->library->normalizeIsbn((string) $request->query('isbn'));
        if (!$isbn) {
            return $this->fail('ISBN 格式不正确，应该是 10 位或 13 位。', 422);
        }

        $existing = Book::with('copies.activeLoan.member')->where('isbn', $isbn)->first();

        // A book we already have needs no web lookup.
        $info = null;
        if (!$existing) {
            try {
                $info = $this->metadata->find($isbn);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'isbn' => $isbn,
            'metadata' => $info,
            'existing' => $existing ? $this->bookPayload($existing) : null,
        ]);
    }

    /** POST /api/library/books  (manager) */
    public function saveBook(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'code' => ['nullable', 'string', 'max:40'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'cover_url' => ['nullable', 'string', 'max:500'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'title.required' => '请填写书名。',
            'quantity.required' => '请填写册数。',
            'quantity.min' => '册数至少是 1。',
        ]);

        if (!empty($data['isbn']) && !$this->library->normalizeIsbn($data['isbn'])) {
            return $this->fail('ISBN 格式不正确，应该是 10 位或 13 位。', 422);
        }

        $book = $this->library->saveBook($data, (int) $data['quantity']);

        return response()->json([
            'success' => true,
            'book' => $this->bookPayload($book->load('copies.activeLoan.member')),
        ]);
    }

    /** PATCH /api/library/books/{book}  (manager) Edit a book. Changing the code renames its copies (CODE.1, CODE.2 ...). */
    public function updateBook(Request $request, Book $book): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ], ['title.required' => '请填写书名。', 'code.required' => '请填写编号。']);

        $code = trim($data['code']);
        if (Book::where('code', $code)->where('id', '!=', $book->id)->exists()) {
            return $this->fail('编号 ' . $code . ' 已经被另一本书使用了。', 422);
        }
        // The new copy labels must not clash with labels of other books either.
        $labels = $book->copies->map(fn ($c) => $code . '.' . $c->number);
        if (Copy::whereIn('code', $labels)->where('book_id', '!=', $book->id)->exists()) {
            return $this->fail('编号 ' . $code . ' 生成的册编号与其他书冲突，请换一个编号。', 422);
        }

        $isbn = null;
        if (trim((string) ($data['isbn'] ?? '')) !== '') {
            $isbn = $this->library->normalizeIsbn($data['isbn']);
            if (!$isbn) {
                return $this->fail('ISBN 格式不正确，应该是 10 位或 13 位。', 422);
            }

            $other = Book::where('isbn', $isbn)->where('id', '!=', $book->id)->first();
            if ($other) {
                return $this->fail('另一本书（编号 ' . $other->code . '）已经使用了这个 ISBN。', 422);
            }
        }

        $blank = fn ($v) => trim((string) $v) === '' ? null : trim((string) $v);
        DB::transaction(function () use ($book, $data, $code, $isbn, $blank) {
            $book->update([
                'code' => $code,
                'title' => trim($data['title']),
                'subtitle' => $blank($data['subtitle'] ?? null),
                'isbn' => $isbn,
                'author' => $blank($data['author'] ?? null),
                'publisher' => $blank($data['publisher'] ?? null),
                'category' => $blank($data['category'] ?? null),
            ]);

            // Keep the labels of the copies in step with the code: CODE.1, CODE.2 ...
            foreach ($book->copies as $copy) {
                $copy->update(['code' => $code . '.' . $copy->number]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => '已保存。',
            'book' => $this->bookPayload($book->load('copies.activeLoan.member')),
        ]);
    }

    /** POST /api/library/books/{book}/copies  (manager) Add one more copy of an existing book */
    public function addCopy(Request $request, Book $book): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $copy = $this->library->addCopy($book);

        return response()->json([
            'success' => true,
            'message' => '已添加第 ' . $copy->number . ' 册（' . $copy->code . '）。',
            'book' => $this->bookPayload($book->load('copies.activeLoan.member')),
        ]);
    }

    /** POST /api/library/copies/{copy}/borrow  { member_id } or { first_name, last_name?, email?, phone? } */
    public function borrow(Request $request, Copy $copy): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['nullable', 'integer', 'exists:library_members,id'],
            'first_name' => ['required_without:member_id', 'nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'first_name.required_without' => '请选择借阅人，或填写新借阅人的名字。',
            'email.email' => '邮箱格式不正确。',
        ]);

        try {
            $member = !empty($data['member_id'])
                ? Member::findOrFail($data['member_id'])
                : $this->library->findOrCreateMember($data);

            $this->library->borrow($copy, $member, $request->user()?->id);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->bookResponse($copy, '已借出给 ' . $member->full_name . '。');
    }

    /** POST /api/library/copies/{copy}/return */
    public function return(Request $request, Copy $copy): JsonResponse
    {
        try {
            $loan = $this->library->return($copy, $request->user()?->id);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->bookResponse($copy, '已归还。借阅人：' . $loan->member->full_name . '。');
    }

    /** POST /api/library/loans/{loan}/renew */
    public function renew(Loan $loan): JsonResponse
    {
        try {
            $loan = $this->library->renew($loan);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->bookResponse($loan->copy, '已续借到 ' . $loan->due_at->format('Y-m-d') . '。');
    }

    /** GET /api/library/loans?member_id= */
    public function loans(Request $request): JsonResponse
    {
        $loans = Loan::active()
            ->with(['copy.book', 'member'])
            ->when($request->filled('member_id'), fn ($q) => $q->where('member_id', (int) $request->query('member_id')))
            ->orderBy('due_at')
            ->get();

        return response()->json([
            'success' => true,
            'loans' => $loans->map(fn ($l) => $this->loanPayload($l, true))->values(),
        ]);
    }

    /** GET /api/library/members?search= */
    public function members(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $members = Member::withCount(['loans as active_loans_count' => fn ($q) => $q->whereNull('returned_at')])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereRaw("CONCAT(last_name, first_name) like ?", [$like]));
            })
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'members' => $members->map(fn ($m) => $this->memberPayload($m))->values(),
        ]);
    }

    /** POST /api/library/members  { first_name, last_name?, email?, phone? } */
    public function createMember(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'first_name.required' => '请填写名字。',
            'email.email' => '邮箱格式不正确。',
        ]);

        $member = $this->library->findOrCreateMember($data);
        $member->loadCount(['loans as active_loans_count' => fn ($q) => $q->whereNull('returned_at')]);

        return response()->json(['success' => true, 'member' => $this->memberPayload($member)]);
    }

    /** POST /api/library/import/books  (manager) CSV / Excel: code;title;author;publisher;category;quantity */
    public function importBooks(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $request->validate(['file' => ['required', 'file']], ['file.required' => '请选择要导入的文件。']);

        $import = new ImportBooks($this->library);
        Excel::import($import, $request->file('file'));

        return response()->json(['success' => true, 'imported' => $import->count]);
    }

    // ---- helpers -----------------------------------------------------------------------------

    private function canManage(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->hasRole(self::MANAGE_ROLE) || $user->hasRole('admin'));
    }

    private function denyUnlessManager(Request $request): ?JsonResponse
    {
        return $this->canManage($request) ? null : $this->fail('你没有管理图书的权限。', 403);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /** Response after a loan change: the refreshed book, so the page can update in place. */
    private function bookResponse(Copy $copy, string $message): JsonResponse
    {
        $book = Book::with('copies.activeLoan.member')->findOrFail($copy->book_id);

        return response()->json(['success' => true, 'message' => $message, 'book' => $this->bookPayload($book)]);
    }

    private function bookPayload(Book $book): array
    {
        return [
            'id' => $book->id,
            'code' => $book->code,
            'isbn' => $book->isbn,
            'title' => $book->title,
            'subtitle' => $book->subtitle,
            'author' => $book->author,
            'publisher' => $book->publisher,
            'category' => $book->category,
            'coverUrl' => $book->cover_url,
            'copies' => $book->copies->map(fn ($c) => [
                'id' => $c->id,
                'number' => $c->number,
                'code' => $c->code,
                'status' => $c->status,
                'loan' => $c->activeLoan ? $this->loanPayload($c->activeLoan) : null,
            ])->values(),
        ];
    }

    private function loanPayload(Loan $loan, bool $withBook = false): array
    {
        $payload = [
            'id' => $loan->id,
            'copyId' => $loan->copy_id,
            'member' => ['id' => $loan->member_id, 'name' => $loan->member?->full_name],
            'borrowedAt' => $loan->borrowed_at->toIso8601String(),
            'dueAt' => $loan->due_at->toIso8601String(),
            'overdue' => $loan->is_overdue,
            'renewals' => $loan->renewals,
        ];

        if ($withBook) {
            $payload['copyCode'] = $loan->copy?->code;
            $payload['book'] = [
                'id' => $loan->copy?->book?->id,
                'title' => $loan->copy?->book?->display_title,
                'author' => $loan->copy?->book?->author,
            ];
        }

        return $payload;
    }

    private function memberPayload(Member $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->full_name,
            'email' => $member->email,
            'phone' => $member->phone,
            'activeLoans' => (int) ($member->active_loans_count ?? 0),
        ];
    }
}
