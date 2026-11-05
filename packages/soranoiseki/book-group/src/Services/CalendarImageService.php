<?php

namespace Soranoiseki\BookGroup\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Manages the artwork of a calendar year, stored on the "public" disk:
 *   images/calendar/<year>/{cover, 01..12, footer}.jpg|png and overlay.png
 */
class CalendarImageService
{
    public const MONTH_SLOTS = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

    /** Allowed extensions per slot (first one wins when several files exist). */
    private const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function slots(): array
    {
        return array_merge(['cover'], self::MONTH_SLOTS, ['overlay', 'footer']);
    }

    public function isValidSlot(string $slot): bool
    {
        return in_array($slot, $this->slots(), true);
    }

    private function disk()
    {
        return Storage::disk('public');
    }

    private function dir(int|string $year): string
    {
        return 'images/calendar/' . $year;
    }

    /**
     * Relative path (on the public disk) of the slot's file, or null.
     * Matching is case-insensitive because older uploads use e.g. "04.JPG".
     */
    public function find(int|string $year, string $slot): ?string
    {
        $candidates = [];
        foreach ($this->disk()->files($this->dir($year)) as $path) {
            $info = pathinfo($path);
            $ext = strtolower($info['extension'] ?? '');
            if (strtolower($info['filename']) === $slot && in_array($ext, self::EXTENSIONS, true)) {
                $candidates[$ext] = $path;
            }
        }

        // The overlay is a transparent PNG; photos prefer JPEG.
        $order = $slot === 'overlay' ? ['png'] : ['jpg', 'jpeg', 'png'];
        foreach ($order as $ext) {
            if (isset($candidates[$ext])) {
                return $candidates[$ext];
            }
        }

        return null;
    }

    /** Absolute filesystem path for the PDF generator, or null. */
    public function absolutePath(int|string $year, string $slot): ?string
    {
        $path = $this->find($year, $slot);

        return $path ? $this->disk()->path($path) : null;
    }

    /** Info for the UI: every slot with existence, URL and size. */
    public function all(int|string $year): array
    {
        $result = [];
        foreach ($this->slots() as $slot) {
            $path = $this->find($year, $slot);
            $result[$slot] = [
                'exists' => (bool) $path,
                'url' => $path ? '/storage/' . $path . '?v=' . $this->disk()->lastModified($path) : null,
                'size' => $path ? $this->disk()->size($path) : null,
            ];
        }

        return $result;
    }

    /** Store an uploaded image for a slot, replacing any previous file of that slot. */
    public function store(int|string $year, string $slot, UploadedFile $file): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;

        $this->remove($year, $slot);

        $name = $slot . '.' . $ext;
        $this->disk()->putFileAs($this->dir($year), $file, $name);

        return $this->dir($year) . '/' . $name;
    }

    public function remove(int|string $year, string $slot): void
    {
        foreach ($this->disk()->files($this->dir($year)) as $path) {
            $info = pathinfo($path);
            if (strtolower($info['filename']) === $slot
                && in_array(strtolower($info['extension'] ?? ''), self::EXTENSIONS, true)) {
                $this->disk()->delete($path);
            }
        }
    }
}
