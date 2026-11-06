<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $table = 'library_books';

    protected $fillable = [
        'code', 'isbn', 'title', 'subtitle', 'author', 'publisher', 'category', 'cover_url', 'is_visible', 'legacy_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, 'book_id')->orderBy('number');
    }

    /** Title with subtitle, as shown in lists. */
    public function getDisplayTitleAttribute(): string
    {
        return $this->subtitle ? $this->title . ' - ' . $this->subtitle : $this->title;
    }
}
