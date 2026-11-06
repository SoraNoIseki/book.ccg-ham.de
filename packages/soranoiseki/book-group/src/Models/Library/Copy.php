<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Copy extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOST = 'lost';
    public const STATUS_RETIRED = 'retired';

    protected $table = 'library_copies';

    protected $fillable = ['book_id', 'number', 'code', 'status', 'legacy_id'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'copy_id');
    }

    /** The loan that has not been returned yet (a copy has at most one). */
    public function activeLoan(): HasOne
    {
        return $this->hasOne(Loan::class, 'copy_id')->whereNull('returned_at');
    }
}
