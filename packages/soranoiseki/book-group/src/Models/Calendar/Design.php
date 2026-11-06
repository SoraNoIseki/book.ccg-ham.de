<?php

namespace Soranoiseki\BookGroup\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named appearance (parameters + custom CSS). Calendars choose one; see CalendarSettings for the parameters.
 */
class Design extends Model
{
    use SoftDeletes;

    protected $table = 'calendar_design';

    protected $fillable = ['name', 'created_by', 'settings', 'styles'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function revisions(): HasMany
    {
        return $this->hasMany(DesignRevision::class, 'design_id');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(Calendar::class, 'design_id');
    }
}
