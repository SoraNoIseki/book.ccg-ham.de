<?php

namespace Soranoiseki\BookGroup\Models\Calendar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'calendar_detail';

    protected $fillable = [
        'year',
        'month',
        'bible_text',
        'bible_source',
        'calendar_id'
    ];
}
