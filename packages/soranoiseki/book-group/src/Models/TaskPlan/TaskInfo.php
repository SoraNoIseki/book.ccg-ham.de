<?php

namespace Soranoiseki\BookGroup\Models\TaskPlan;

use MongoDB\Laravel\Eloquent\Model;

class TaskInfo extends Model
{
    protected $connection = 'mongodb-task';

    protected $fillable = [
        '1st_week',
        '2nd_week',
        '3rd_week',
        '4th_week',
        '5th_week',
        'group_id',
    ];

    public function getWeek1Attribute()
    {
        return $this->attributes['1st_week'];
    }

    public function getWeek2Attribute()
    {
        return $this->attributes['2nd_week'];
    }

    public function getWeek3Attribute()
    {
        return $this->attributes['3rd_week'];
    }

    public function getWeek4Attribute()
    {
        return $this->attributes['4th_week'];
    }

    public function getWeek5Attribute()
    {
        return $this->attributes['5th_week'];
    }

    public function setWeekAttribute(int $weekOfMonth, $value)
    {
        if ($weekOfMonth < 1 || $weekOfMonth > 5) {
            return;
        }

        match ($weekOfMonth) {
            1 => $this->attributes['1st_week'] = $value,
            2 => $this->attributes['2nd_week'] = $value,
            3 => $this->attributes['3rd_week'] = $value,
            4 => $this->attributes['4th_week'] = $value,
            5 => $this->attributes['5th_week'] = $value,
        };
    }

    public function getWeekAttribute(int $weekOfMonth)
    {
        if ($weekOfMonth < 1 || $weekOfMonth > 5) {
            return '';
        }

        $value = '';
        match ($weekOfMonth) {
            1 => $value = $this->attributes['1st_week'],
            2 => $value = $this->attributes['2nd_week'],
            3 => $value = $this->attributes['3rd_week'],
            4 => $value = $this->attributes['4th_week'],
            5 => $value = $this->attributes['5th_week'],
        };

        return $value;
    }

    public function initWeekAttributes()
    {
        $this->attributes['1st_week'] = '';
        $this->attributes['2nd_week'] = '';
        $this->attributes['3rd_week'] = '';
        $this->attributes['4th_week'] = '';
        $this->attributes['5th_week'] = '';
    }

}
