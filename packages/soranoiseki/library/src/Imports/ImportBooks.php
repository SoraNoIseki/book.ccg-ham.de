<?php

namespace Soranoiseki\Library\Imports;

use Soranoiseki\Library\Models\Book;
use Soranoiseki\Library\Models\Copy;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ImportBooks implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        $code = $row[0];
        $title = $row[1];
        $author = $row[2];
        $publish = $row[3];
        $category = $row[4];
        $quantity = $row[5];
        $existCopies = 0;
        
        try {
            // update book data
            $book = Book::where('call_nmbr1', $code)->firstOrFail();
            $book->fill([
                'title' => $title,
                'responsibility_stmt' => $publish,
                'author' => $author,
                'topic1' => $category,
            ])->save();
            $existCopies = $book->copies->count();
        } catch (ModelNotFoundException $e) {
            $book = Book::create([
                'create_dt' => now(),
                'last_change_dt' => now(),
                'last_change_userid' => 1,
                'material_cd' => 2,
                'collection_cd' => 2,
                'call_nmbr1' => $code,
                'call_nmbr2' => '',
                'call_nmbr3' => '',
                'title' => $title,
                'title_remainder' => '',
                'responsibility_stmt' => $publish,
                'author' => $author,
                'topic1' => $category,
                'topic2' => '',
                'topic3' => '',
                'topic4' => '',
                'topic5' => '',
                'opac_flg' => 'Y',
            ]);
        }

        // create book & copies
        for($i = $existCopies + 1; $i <= (int)$quantity; $i++) {
            $copy = $code . '.' . $i;
            $book->copies()->create([
                'bibid' => $book->bibid,
                'create_dt' => now(),
                'copy_desc' => $copy,
                'barcode_nmbr' => '0',
                'status_cd' => 'in',
                'status_begin_dt' => now(),
                'due_back_dt' => null,
                'mbrid' => null,
                'renewal_count' => '0',
            ]);
        }
    }
}
