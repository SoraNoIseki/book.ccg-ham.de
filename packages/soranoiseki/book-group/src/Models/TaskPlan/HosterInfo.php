<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class HosterInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $table = 'hoster_info';

}
