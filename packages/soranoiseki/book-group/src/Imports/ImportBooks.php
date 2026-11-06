<?php

namespace Soranoiseki\BookGroup\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Soranoiseki\BookGroup\Services\LibraryService;

/**
 * Columns: code; title; author; publisher; category; quantity. Existing codes are updated and
 * topped up to the given quantity.
 */
class ImportBooks implements OnEachRow
{
    public int $count = 0;

    public function __construct(private LibraryService $library)
    {
    }

    public function onRow(Row $row)
    {
        $cells = $row->toArray();
        $code = trim((string) ($cells[0] ?? ''));
        $title = trim((string) ($cells[1] ?? ''));

        if ($code === '' || $title === '') {
            return;
        }

        $this->library->saveBook([
            'code' => $code,
            'title' => $title,
            'author' => $cells[2] ?? null,
            'publisher' => $cells[3] ?? null,
            'category' => $cells[4] ?? null,
        ], max(1, (int) ($cells[5] ?? 1)));

        $this->count++;
    }
}
