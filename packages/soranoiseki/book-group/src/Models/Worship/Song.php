<?php

namespace Soranoiseki\BookGroup\Models\Worship;

use MongoDB\Laravel\Eloquent\Model;

class Song extends Model
{
    protected $connection = 'mongodb-hymn';

    protected $collection = 'hymn_info';

}
