<?php

namespace Soranoiseki\BookGroup\Services;

use Illuminate\Support\Facades\Http;
use Soranoiseki\BookGroup\Models\Library\BookDatabase;

/**
 * Looks up title, author and publisher of an ISBN. Answers are cached in the book_database table,
 * so each ISBN is only requested from Google Books once.
 */
class BookMetadataService
{
    /** @return array{title:?string,subtitle:?string,author:?string,publisher:?string,cover_url:?string}|null */
    public function find(string $isbn): ?array
    {
        $cached = BookDatabase::where('isbn', $isbn)->first();
        if ($cached) {
            return $this->map(json_decode((string) $cached->data, true) ?: []);
        }

        $response = Http::timeout(8)->get('https://www.googleapis.com/books/v1/volumes', ['q' => 'isbn:' . $isbn]);

        if (!$response->successful() || (int) $response->json('totalItems', 0) === 0) {
            return null;
        }

        $info = $response->json('items.0.volumeInfo', []);
        BookDatabase::create(['isbn' => $isbn, 'data' => json_encode($info)]);

        return $this->map($info);
    }

    private function map(array $info): array
    {
        $cover = $info['imageLinks']['thumbnail'] ?? null;

        return [
            'title' => $info['title'] ?? null,
            'subtitle' => $info['subtitle'] ?? null,
            'author' => isset($info['authors']) ? implode(', ', $info['authors']) : null,
            'publisher' => $info['publisher'] ?? null,
            'cover_url' => $cover ? preg_replace('#^http://#', 'https://', $cover) : null,
        ];
    }
}
