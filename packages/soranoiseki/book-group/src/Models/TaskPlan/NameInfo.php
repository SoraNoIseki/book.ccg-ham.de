<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

use MongoDB\Laravel\Eloquent\Model;

class NameInfo extends Model
{
    protected $connection = 'mongodb-task';

    protected $collection = 'name_info';

    public function getNamesAttribute()
    {
        return explode('+', $this->name);
    }

}
