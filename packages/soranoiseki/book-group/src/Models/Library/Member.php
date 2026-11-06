<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $table = 'library_members';

    protected $fillable = [
        'card_no', 'first_name', 'last_name', 'email', 'phone', 'membership_ends_at', 'legacy_id',
    ];

    protected $casts = [
        'membership_ends_at' => 'date',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id');
    }

    /** Chinese names are written family name first and without a space. */
    public function getFullNameAttribute(): string
    {
        return trim(trim($this->last_name) . trim($this->first_name));
    }
}
