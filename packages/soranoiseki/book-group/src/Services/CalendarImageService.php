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

    /** Largest stored image: A3 (297 x 420 mm) at 300 dpi. Larger uploads are scaled down, never up. */
    public const MAX_WIDTH = 3508;
    public const MAX_HEIGHT = 4961;
    public const JPEG_QUALITY = 90;

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

    /** Absolute folder of a year's images (the only place custom CSS may read files from). */
    public function directory(int|string $year): string
    {
        return $this->disk()->path($this->dir($year));
    }

    /** Absolute filesystem path for the PDF generator, or null. */
    public function absolutePath(int|string $year, string $slot): ?string
    {
        $path = $this->find($year, $slot);

        return $path ? $this->disk()->path($path) : null;
    }

    /** Info for the UI: every slot with existence, URL and size. */
    public function all(int|string $year, ?int $calendarId = null): array
    {
        $result = [];
        foreach ($this->slots() as $slot) {
            $path = $this->find($year, $slot);
            $result[$slot] = [
                'exists' => (bool) $path,
                'url' => $path ? '/storage/' . $path . '?v=' . $this->disk()->lastModified($path) : null,
                'size' => $path ? $this->disk()->size($path) : null,
                // Small and medium copies for the page (see preview()); same version suffix as the original
                'thumb' => $path && $calendarId ? $this->previewUrl($calendarId, $slot, 'thumb', $path) : null,
                'preview' => $path && $calendarId ? $this->previewUrl($calendarId, $slot, 'view', $path) : null,
            ];
        }

        return $result;
    }

    private function previewUrl(int $calendarId, string $slot, string $size, string $path): string
    {
        return "/api/calendar/{$calendarId}/images/{$slot}/preview/{$size}?v=" . $this->disk()->lastModified($path) . '-' . $this->disk()->size($path);
    }

    /**
     * Store an uploaded image for a slot, replacing any previous file of that slot.
     * Only the monthly photos are processed: they become JPEG and are scaled down to fit MAX_WIDTH x MAX_HEIGHT
     * (A3 at 300 dpi). Cover, overlay and footer are stored as uploaded.
     * If the image cannot be processed (no GD, unreadable), the original file is stored unchanged.
     */
    public function store(int|string $year, string $slot, UploadedFile $file): string
    {
        $bytes = in_array($slot, self::MONTH_SLOTS, true) ? $this->process($file->getRealPath(), self::MAX_WIDTH, self::MAX_HEIGHT) : null;

        if ($bytes !== null) {
            $ext = 'jpg';
        } else {
            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        }

        $this->remove($year, $slot);

        $name = $slot . '.' . $ext;
        if ($bytes !== null) {
            $this->disk()->put($this->dir($year) . '/' . $name, $bytes);
        } else {
            $this->disk()->putFileAs($this->dir($year), $file, $name);
        }

        return $this->dir($year) . '/' . $name;
    }

    /**
     * Scale an image down to fit $maxWidth x $maxHeight (never up) and encode it as JPEG, or as PNG with transparency.
     * Returns the encoded bytes, or null when the image cannot be processed.
     */
    private function process(string $path, int $maxWidth, int $maxHeight, bool $toJpeg = true, int $quality = self::JPEG_QUALITY): ?string
    {
        if (!function_exists('imagecreatefromstring') || !($info = @getimagesize($path))) {
            return null;
        }
        $type = $info[2];

        @ini_set('memory_limit', '768M'); // a decoded 50 MB photo takes several hundred MB
        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (!$src) {
            return null;
        }

        // Phones store the orientation in EXIF; GD would show such photos sideways
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $angle = [3 => 180, 6 => -90, 8 => 90][(int) (@exif_read_data($path)['Orientation'] ?? 1)] ?? 0;
            if ($angle && ($rotated = imagerotate($src, $angle, 0))) {
                $src = $rotated;
            }
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $scale = min(1, $maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if ($toJpeg) {
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // transparent PNG parts become white
        } else {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        $toJpeg ? imagejpeg($dst, null, $quality) : imagepng($dst, null, 6);
        $bytes = (string) ob_get_clean();

        return $bytes === '' ? null : $bytes;
    }

    // --- Preview copies -------------------------------------------------------------------------
    // The page shows small and medium copies instead of the big print files. They are made on first use and
    // kept on the "local" disk (not public); they can be deleted at any time and are made again when needed.

    /** Long side in px and JPEG quality of each preview size */
    private const PREVIEWS = ['thumb' => [1000, 80], 'view' => [2000, 85]];

    public function isValidPreviewSize(string $size): bool
    {
        return isset(self::PREVIEWS[$size]);
    }

    private function previewDisk()
    {
        return Storage::disk('local');
    }

    private function previewDir(int|string $year): string
    {
        return 'calendar-previews/' . $year;
    }

    /**
     * Absolute path of a preview copy of a slot's image (made when missing), or null if the slot is empty.
     * Images that are small enough already are served as they are. Falls back to the original if it cannot be scaled.
     */
    public function preview(int|string $year, string $slot, string $size): ?string
    {
        $path = $this->find($year, $slot);
        if (!$path || !isset(self::PREVIEWS[$size])) {
            return null;
        }

        $original = $this->disk()->path($path);
        [$max, $quality] = self::PREVIEWS[$size];
        $info = @getimagesize($original);
        if (!$info || ($info[0] <= $max && $info[1] <= $max)) {
            return $original;
        }

        $toJpeg = $slot !== 'overlay' && $slot !== 'footer'; // overlay and footer may be transparent
        $key = $slot . '-' . $size . '-' . $this->disk()->lastModified($path) . '-' . $this->disk()->size($path) . '.' . ($toJpeg ? 'jpg' : 'png');
        $cached = $this->previewDir($year) . '/' . $key;
        if (!$this->previewDisk()->exists($cached)) {
            $bytes = $this->process($original, $max, $max, $toJpeg, $quality);
            if ($bytes === null) {
                return $original;
            }
            $this->previewDisk()->put($cached, $bytes);
            // Older copies of the same slot and size are out of date now (the two sizes are made independently)
            foreach ($this->previewDisk()->files($this->previewDir($year)) as $file) {
                if (str_starts_with(basename($file), $slot . '-' . $size . '-') && $file !== $cached) {
                    $this->previewDisk()->delete($file);
                }
            }
        }

        return $this->previewDisk()->path($cached);
    }

    /** Delete the preview copies of one slot, or of the whole year when no slot is given. */
    public function forgetPreviews(int|string $year, ?string $slot = null): void
    {
        foreach ($this->previewDisk()->files($this->previewDir($year)) as $file) {
            if ($slot === null || str_starts_with(basename($file), $slot . '-')) {
                $this->previewDisk()->delete($file);
            }
        }
    }

    /** Years that have an image folder, e.g. [2025, 2026] */
    public function years(): array
    {
        $years = [];
        foreach ($this->disk()->directories('images/calendar') as $dir) {
            if (ctype_digit(basename($dir))) {
                $years[] = (int) basename($dir);
            }
        }
        sort($years);

        return $years;
    }

    /**
     * Bring a monthly photo that was uploaded before the size limit in line with the rules of store().
     * Returns [oldBytes, newBytes] (or null when the slot is empty or nothing has to change).
     * Photos that are already JPEGs within the limits are left alone (no second JPEG compression).
     * With $dryRun nothing is written; the new size is then estimated by doing the work in memory.
     */
    public function optimizeExisting(int|string $year, string $slot, bool $dryRun = false): ?array
    {
        $path = in_array($slot, self::MONTH_SLOTS, true) ? $this->find($year, $slot) : null;
        if (!$path) {
            return null;
        }

        $absolute = $this->disk()->path($path);
        $isJpeg = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true);
        $info = @getimagesize($absolute);
        if ($isJpeg && $info && $info[0] <= self::MAX_WIDTH && $info[1] <= self::MAX_HEIGHT) {
            return null;
        }

        $bytes = $this->process($absolute, self::MAX_WIDTH, self::MAX_HEIGHT);
        if ($bytes === null) {
            return null;
        }
        $ext = 'jpg';
        $old = $this->disk()->size($path);

        if (!$dryRun) {
            $this->remove($year, $slot);
            $this->disk()->put($this->dir($year) . '/' . $slot . '.' . $ext, $bytes);
        }

        return [$old, strlen($bytes)];
    }

    public function remove(int|string $year, string $slot): void
    {
        $this->forgetPreviews($year, $slot);
        foreach ($this->disk()->files($this->dir($year)) as $path) {
            $info = pathinfo($path);
            if (strtolower($info['filename']) === $slot
                && in_array(strtolower($info['extension'] ?? ''), self::EXTENSIONS, true)) {
                $this->disk()->delete($path);
            }
        }
    }

    /**
     * Rearrange the monthly photos: $order[i] is the month whose photo moves to month i + 1 (a permutation of 1..12).
     * Files are parked under a temporary name first so that two months can swap without overwriting each other.
     */
    public function reorderMonths(int|string $year, array $order): void
    {
        $moves = []; // old month => new month
        foreach (array_values($order) as $i => $from) {
            if ((int) $from !== $i + 1) {
                $moves[(int) $from] = $i + 1;
            }
        }

        $this->forgetPreviews($year);
        $parked = [];
        foreach ($moves as $from => $to) {
            $slot = sprintf('%02d', $from);
            $path = $this->find($year, $slot);
            if ($path) {
                $tmp = $this->dir($year) . '/.swap-' . $slot . '.' . strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $this->disk()->move($path, $tmp);
                $parked[$tmp] = $to;
            }
            // Clears leftovers of the slot (e.g. a second file with another extension)
            $this->remove($year, $slot);
        }

        foreach ($parked as $tmp => $to) {
            $this->disk()->move($tmp, $this->dir($year) . '/' . sprintf('%02d', $to) . '.' . pathinfo($tmp, PATHINFO_EXTENSION));
        }
    }
}
