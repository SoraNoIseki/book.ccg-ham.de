<?php

namespace Soranoiseki\BookGroup\Models\Worship;

use MongoDB\Laravel\Eloquent\Model;

class Song extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'song_info';

}
