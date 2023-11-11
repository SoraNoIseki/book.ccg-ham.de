<?php

namespace Soranoiseki\BookGroup\Models\Calendar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'calendar_event';

    protected $fillable = [
        'year',
        'date',
        'name',
        'type',
        'calendar_id'
    ];
}
