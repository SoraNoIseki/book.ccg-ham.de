<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class CookInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'cook_info';

}
