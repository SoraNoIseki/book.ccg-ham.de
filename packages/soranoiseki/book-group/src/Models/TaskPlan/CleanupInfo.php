<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class CleanupInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $table = 'cleanup_info';

}
