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

    protected $casts = [
        'settings' => 'array',
    ];

    public function events() {
        return $this->hasMany(Event::class, 'calendar_id', 'id');
    }

    public function design() {
        return $this->belongsTo(Design::class, 'design_id');
    }

    /** The design this calendar renders with; falls back to the first design if none is set. */
    public function effectiveDesign(): ?Design {
        return $this->design ?? Design::orderBy('id')->first();
    }

    public function details() {
        return $this->hasMany(Detail::class, 'calendar_id', 'id');
    }

}
