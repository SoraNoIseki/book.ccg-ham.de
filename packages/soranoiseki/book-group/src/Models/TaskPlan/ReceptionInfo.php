<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class ReceptionInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'reception_info';

}
