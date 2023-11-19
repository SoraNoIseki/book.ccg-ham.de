<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Soranoiseki\BookGroup\Models\Library\Copy;

class BookDatabase extends Model
{
    use HasFactory;

    protected $table = 'book_database';

    protected $fillable = [
        'isbn',
        'data',
    ];

}
