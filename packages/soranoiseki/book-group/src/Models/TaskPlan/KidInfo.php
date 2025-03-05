<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class KidInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'kid_info';

}
