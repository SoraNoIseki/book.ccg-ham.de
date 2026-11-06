<?php

namespace Soranoiseki\BookGroup\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded change of a design. Rows are never updated or deleted.
 */
class DesignRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'calendar_design_revision';

    protected $fillable = [
        'design_id',
        'user_id',
        'user_name',
        'action',
        'restored_from_id',
        'settings',
        'styles',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
    ];

    public function restoredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_id');
    }
}
