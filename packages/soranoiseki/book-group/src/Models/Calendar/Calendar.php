<?php

namespace Soranoiseki\BookGroup\Models\Calendar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Soranoiseki\BookGroup\Models\Calendar\Detail;

class Calendar extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'calendar';

    public function events() {
        return $this->hasMany(Event::class, 'calendar_id', 'id');
    }

    public function details() {
        return $this->hasMany(Detail::class, 'calendar_id', 'id');
    }

}
