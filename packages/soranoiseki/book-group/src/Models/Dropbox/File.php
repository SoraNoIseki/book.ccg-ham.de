<?php

namespace Soranoiseki\BookGroup\Models\Dropbox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Soranoiseki\BookGroup\Models\Library\Copy;

class File extends Model
{
    /**
     * @var string
     */
    protected $table = 'dropbox_files';

    protected $fillable = [
        'date',
        'file_name',
        'file_path',
        'share_link',
        'type',
        'raw',
    ];

}
