<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

class SongLeaderInfo extends TaskInfo
{
    protected $connection = 'mongodb-task';

    protected $collection = 'song_leader_info';

}
