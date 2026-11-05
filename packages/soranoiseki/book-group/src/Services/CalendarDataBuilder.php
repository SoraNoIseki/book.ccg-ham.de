<?php

namespace Soranoiseki\BookGroup\Services;

use Overtrue\ChineseCalendar\Calendar as ChineseCalendar;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Detail;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Soranoiseki\BookGroup\Models\Calendar\Holiday;

/**
 * Collects everything the PDF generator needs and returns it as a plain array (JSON-ready).
 * All layout and styling lives in the Python script; PHP only supplies data and file paths.
 */
class CalendarDataBuilder
{
    private const MONTH_DE = [
        'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
    ];

    private const WEEKDAYS = [
        ['de' => 'Montag', 'zh' => '一', 'weekend' => false],
        ['de' => 'Dienstag', 'zh' => '二', 'weekend' => false],
        ['de' => 'Mittwoch', 'zh' => '三', 'weekend' => false],
        ['de' => 'Donnerstag', 'zh' => '四', 'weekend' => false],
        ['de' => 'Freitag', 'zh' => '五', 'weekend' => false],
        ['de' => 'Samstag', 'zh' => '六', 'weekend' => true],
        ['de' => 'Sonntag', 'zh' => '日', 'weekend' => true],
    ];

    private ChineseCalendar $lunar;
    private array $events = [];
    private array $holidays = [];

    public function __construct(private CalendarImageService $images)
    {
        $this->lunar = new ChineseCalendar();
    }

    public function build(Calendar $calendar): array
    {
        $year = (int) $calendar->year;

        $this->events = [];
        foreach (Event::where('year', $year)->orderBy('date')->get() as $event) {
            $this->events[$event->date instanceof \DateTimeInterface ? $event->date->format('Y-m-d') : (string) $event->date][] = [
                'type' => $event->type ?: 'yearly',
                'lines' => array_values(array_filter(array_map('trim', explode('|', (string) $event->name)), 'strlen')),
            ];
        }

        $this->holidays = [];
        foreach (Holiday::where('year', $year)->get() as $holiday) {
            $key = $holiday->date instanceof \DateTimeInterface ? $holiday->date->format('Y-m-d') : (string) $holiday->date;
            $this->holidays[$key] = $holiday->name_cn;
        }

        $details = [];
        foreach (Detail::where('year', $year)->get() as $detail) {
            $details[(int) $detail->month] = $detail;
        }

        $months = [];
        foreach (range(1, 12) as $month) {
            $slot = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $detail = $details[$month] ?? null;
            $weeks = $this->buildMonth($year, $month);

            $months[] = [
                'month' => $month,
                'title_zh' => $year . '年' . $month . '月',
                'title_de' => self::MONTH_DE[$month - 1],
                'image' => $this->images->absolutePath($year, $slot),
                'bible_lines' => $detail ? array_values(array_filter(array_map('trim', explode('|', (string) $detail->bible_text)), 'strlen')) : [],
                'bible_source' => $detail->bible_source ?? '',
                'days' => $weeks,
                'week_count' => (int) (count($weeks) / 7),
            ];
        }

        return [
            'year' => $year,
            'weekdays' => self::WEEKDAYS,
            'cover' => $this->images->absolutePath($year, 'cover'),
            'overlay' => $this->images->absolutePath($year, 'overlay'),
            'footer' => $this->images->absolutePath($year, 'footer'),
            'months' => $months,
        ];
    }

    /** Days of one month as a flat list, padded with neighbouring-month days to full Monday-Sunday weeks. */
    private function buildMonth(int $year, int $month): array
    {
        $first = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $last = $first->modify('last day of this month');

        $start = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
        $end = $last->modify('+' . (7 - (int) $last->format('N')) . ' days');

        $days = [];
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            $inMonth = $d->format('Ym') === $first->format('Ym');
            $days[] = $this->buildDay($d, $inMonth);
        }

        return $days;
    }

    private function buildDay(\DateTimeImmutable $date, bool $inMonth): array
    {
        $key = $date->format('Y-m-d');
        $info = $this->lunar->solar((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        if (!empty($info['term'])) {
            $lunar = $info['term'];
        } elseif ((int) $info['lunar_day'] === 1) {
            $lunar = $info['lunar_month_chinese'];
        } else {
            $lunar = $info['lunar_day_chinese'];
        }

        $holiday = $this->holidays[$key] ?? null;
        $weekend = in_array((int) $info['week_no'], [0, 6], true);

        return [
            'date' => $key,
            'day' => (int) $date->format('j'),
            'in_month' => $inMonth,
            'red' => (bool) $holiday || $weekend,
            'lunar' => $lunar,
            'holiday' => $holiday,
            'events' => $this->events[$key] ?? [],
        ];
    }
}
