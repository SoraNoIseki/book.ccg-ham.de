<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class TopicInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'topic_info';

}
