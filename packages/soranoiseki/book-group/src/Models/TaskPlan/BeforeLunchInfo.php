<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class BeforeLunchInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'before_lunch_info';

}
