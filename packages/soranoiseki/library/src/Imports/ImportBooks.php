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
        
        try {
            // update book data
            $book = Book::where('call_nmbr1', $row[0])->firstOrFail();
            $book->fill([
                'title' => $row[1],
                'responsibility_stmt' => $row[3],
                'author' => $row[2],
            ]);
        } catch (ModelNotFoundException $e) {
            // create book & copies
            $book = Book::create([
                'create_dt' => now(),
                'last_change_dt' => now(),
                'last_change_userid' => 1,
                'material_cd' => 2,
                'collection_cd' => 2,
                'call_nmbr1' => $row[0],
                'call_nmbr2' => '',
                'call_nmbr3' => '',
                'title' => $row[1],
                'title_remainder' => '',
                'responsibility_stmt' => $row[3],
                'author' => $row[2],
                'topic1' => '',
                'topic2' => '',
                'topic3' => '',
                'topic4' => '',
                'topic5' => '',
                'opac_flg' => 'Y',
            ]);

            for($i = 1; $i <= (int)$row[4]; $i++) {
                $copy = $row[0] . '.' . $i;
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
}
