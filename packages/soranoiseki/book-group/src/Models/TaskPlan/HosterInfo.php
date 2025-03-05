<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class HosterInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'hoster_info';

}
