<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class CleanupAfterInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $table = 'cleanup_after_info';

}
