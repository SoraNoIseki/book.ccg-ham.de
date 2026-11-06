<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $table = 'library_loans';

    protected $fillable = [
        'copy_id', 'member_id', 'borrowed_at', 'due_at', 'returned_at', 'renewals', 'borrowed_by', 'returned_by',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class, 'copy_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    public function getIsOverdueAttribute(): bool
    {
        // Due dates count as whole days: a book due today is not overdue until tomorrow.
        return $this->returned_at === null && $this->due_at->copy()->endOfDay()->isPast();
    }
}
