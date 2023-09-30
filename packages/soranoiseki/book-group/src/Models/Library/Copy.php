<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Member;

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

    public function member() {
        return $this->belongsTo(Member::class, 'mbrid', 'mbrid');
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