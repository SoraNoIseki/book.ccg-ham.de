<?php

namespace Soranoiseki\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Soranoiseki\Library\Models\Book;

class Copy extends Model
{
    use HasFactory;

    protected $connection = 'openbiblio';

    protected $table = 'biblio_copy';

    protected $primaryKey = 'bibid,copyid';

    public $timestamps = false;

    public function book() {
        return $this->belongsTo(Book::class, 'bibid', 'bibid');
    }

    protected $fillable = [
        'bibid',
        'copyid',
        'create_dt',
        'copy_desc',
        'barcode_nmbr',
        'status_cd',
        'status_begin_dt',
        'due_back_dt',
        'mbrid',
        'renewal_count',
    ];

}