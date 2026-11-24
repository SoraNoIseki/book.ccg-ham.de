<?php

namespace Soranoiseki\BookGroup\Console\Commands;

use Illuminate\Console\Command;
use Soranoiseki\BookGroup\Services\CalendarImageService;

/** Scale down and convert monthly calendar photos that were uploaded before uploads were limited (see CalendarImageService::store). */
class OptimizeCalendarImages extends Command
{
    protected $signature = 'book:optimize-calendar-images {year? : Only this year (default: all years)} {--dry-run : Show what would change without writing anything}';

    protected $description = 'Scale down monthly calendar photos to A3 size at 300 dpi and convert them to JPG';

    public function handle(CalendarImageService $images): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $years = $this->argument('year') ? [(int) $this->argument('year')] : $images->years();

        if (!$years) {
            $this->warn('No calendar images found.');

            return self::SUCCESS;
        }

        $changed = 0;
        $saved = 0;
        foreach ($years as $year) {
            foreach ($images::MONTH_SLOTS as $slot) {
                $result = $images->optimizeExisting($year, $slot, $dryRun);
                if (!$result) {
                    continue;
                }
                [$old, $new] = $result;
                $changed++;
                $saved += $old - $new;
                $this->line(sprintf('%d/%s: %s -> %s', $year, $slot, $this->size($old), $this->size($new)));
            }
        }

        $this->info(sprintf(
            '%s%d image(s) changed, %s saved.%s',
            $dryRun ? '[Dry run] ' : '',
            $changed,
            $this->size(max(0, $saved)),
            $dryRun ? ' Nothing was written.' : ''
        ));

        return self::SUCCESS;
    }

    private function size(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }
}
