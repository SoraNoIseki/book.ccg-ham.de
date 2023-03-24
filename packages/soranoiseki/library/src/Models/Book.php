<?php

namespace Soranoiseki\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Soranoiseki\Library\Models\Copy;

class Book extends Model
{
    use HasFactory;

    protected $connection = 'openbiblio';

    protected $table = 'biblio';

    protected $primaryKey = 'bibid';

    public $timestamps = false;

    public function copies() {
        return $this->hasMany(Copy::class, 'bibid', 'bibid');
    }

    protected $fillable = [
        'create_dt',
        'last_change_dt',
        'last_change_userid',
        'material_cd',
        'collection_cd',
        'call_nmbr1',
        'call_nmbr2',
        'call_nmbr3',
        'title',
        'title_remainder',
        'responsibility_stmt',
        'author',
        'topic1',
        'topic2',
        'topic3',
        'topic4',
        'topic5',
        'opac_flg',
    ];

}
