<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Soranoiseki\BookGroup\Imports\Calendar\ImportBibleTexts;
use Soranoiseki\BookGroup\Imports\Calendar\ImportEvents;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Detail;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Soranoiseki\BookGroup\Models\Calendar\Holiday;
use Soranoiseki\BookGroup\Services\CalendarDataBuilder;
use Soranoiseki\BookGroup\Services\CalendarImageService;
use Soranoiseki\Core\Controllers\Controller;
use Symfony\Component\Process\Process;

/**
 * JSON API for the calendar page (Vue). PHP only collects data; the PDF itself is
 * rendered by storage/scripts/calendar/calendar_pdf.py.
 */
class CalendarApiController extends Controller
{
    public function __construct(private CalendarImageService $images)
    {
    }

    /** Calendars are usually prepared at the end of the year for the next year. */
    private function defaultYear(): int
    {
        return (int) date('Y') + 1;
    }

    /**
     * GET /api/calendar?year=YYYY
     */
    public function show(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?: $this->defaultYear());
        $calendar = Calendar::where('year', $year)->first();

        return response()->json($this->payload($year, $calendar));
    }

    /**
     * POST /api/calendar  { year }
     */
    public function create(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?: $this->defaultYear());

        if ($year < 2000 || $year > 2100) {
            return $this->fail('年份无效。', 422);
        }

        $calendar = Calendar::where('year', $year)->first();
        if ($calendar) {
            return $this->fail($year . ' 年的日历已经存在。', 409);
        }

        $calendar = new Calendar();
        $calendar->year = $year;
        $calendar->save();

        $message = '已创建 ' . $year . ' 年日历。';
        try {
            $this->fetchHolidays($year);
        } catch (\Throwable $e) {
            $message .= '但节假日获取失败，请稍后重试。';
        }

        return response()->json($this->payload($year, $calendar) + ['message' => $message]);
    }

    /**
     * POST /api/calendar/{calendar}/events  (multipart: file = CSV with columns date,event)
     */
    public function importEvents(Request $request, Calendar $calendar): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        try {
            Event::where('calendar_id', $calendar->id)->delete();
            Excel::import(new ImportEvents($calendar), $request->file('file'));
        } catch (\Throwable $e) {
            return $this->fail('大事历导入失败：' . $e->getMessage(), 422);
        }

        return response()->json($this->payload((int) $calendar->year, $calendar) + ['message' => '大事历已导入。']);
    }

    /**
     * POST /api/calendar/{calendar}/bible-texts  (multipart: file = CSV with columns year,month,text,source)
     */
    public function importBibleTexts(Request $request, Calendar $calendar): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        try {
            Excel::import(new ImportBibleTexts($calendar), $request->file('file'));
        } catch (\Throwable $e) {
            return $this->fail('每月经文导入失败：' . $e->getMessage(), 422);
        }

        return response()->json($this->payload((int) $calendar->year, $calendar) + ['message' => '每月经文已导入。']);
    }

    /**
     * POST /api/calendar/{calendar}/images/{slot}  (multipart: file)
     * slot = cover | 01..12 | overlay | footer
     */
    public function uploadImage(Request $request, Calendar $calendar, string $slot): JsonResponse
    {
        if (!$this->images->isValidSlot($slot)) {
            return $this->fail('未知的图片位置。', 404);
        }

        $request->validate([
            'file' => ($slot === 'overlay' ? 'required|image|mimes:png' : 'required|image|mimes:jpg,jpeg,png') . '|max:51200',
        ], [
            'file.mimes' => $slot === 'overlay' ? '叠加层必须是 PNG 图片。' : '图片格式必须是 JPG 或 PNG。',
            'file.max' => '图片不能超过 50 MB。',
            'file.image' => '请上传图片文件。',
        ]);

        $this->images->store($calendar->year, $slot, $request->file('file'));

        return response()->json(['success' => true, 'images' => $this->images->all($calendar->year), 'message' => '图片已上传。']);
    }

    /**
     * DELETE /api/calendar/{calendar}/images/{slot}
     */
    public function deleteImage(Calendar $calendar, string $slot): JsonResponse
    {
        if (!$this->images->isValidSlot($slot)) {
            return $this->fail('未知的图片位置。', 404);
        }

        $this->images->remove($calendar->year, $slot);

        return response()->json(['success' => true, 'images' => $this->images->all($calendar->year), 'message' => '图片已删除。']);
    }

    /**
     * POST /api/calendar/{calendar}/holidays  (retry when the holiday api failed on creation)
     */
    public function refreshHolidays(Calendar $calendar): JsonResponse
    {
        try {
            $this->fetchHolidays((int) $calendar->year);
        } catch (\Throwable $e) {
            return $this->fail('节假日获取失败，请稍后重试。', 502);
        }

        return response()->json($this->payload((int) $calendar->year, $calendar) + ['message' => '节假日已更新。']);
    }

    /**
     * POST /api/calendar/{calendar}/pdf
     * Collect data -> JSON file -> python renders the PDF -> stream it back.
     */
    public function pdf(Calendar $calendar, CalendarDataBuilder $builder)
    {
        $data = $builder->build($calendar);

        $dir = storage_path('app/tmp/calendar');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $base = $dir . '/calendar_' . $calendar->year . '_' . uniqid();
        $jsonPath = $base . '.json';
        $pdfPath = $base . '.pdf';

        file_put_contents($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $process = new Process([
            env('COMMON_PYTHON_PATH', '/usr/bin/python3'),
            storage_path('scripts/calendar/calendar_pdf.py'),
            $jsonPath,
            $pdfPath,
        ]);
        $process->setTimeout(600);
        $process->run();

        @unlink($jsonPath);

        if (!$process->isSuccessful() || !is_file($pdfPath)) {
            @unlink($pdfPath);
            $detail = trim(implode("\n", array_slice(explode("\n", trim($process->getErrorOutput())), -6)));

            return $this->fail('生成 PDF 失败：' . ($detail !== '' ? $detail : '未知错误'), 500);
        }

        return response()
            ->download($pdfPath, 'calendar_' . $calendar->year . '.pdf', ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    // ----------------------------
    // internal helpers
    // ----------------------------

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function payload(int $year, ?Calendar $calendar): array
    {
        $events = [];
        $details = [];
        $images = [];

        if ($calendar) {
            $events = Event::where('year', $year)->orderBy('date')->get(['id', 'date', 'name', 'type'])
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'date' => $e->date instanceof \DateTimeInterface ? $e->date->format('Y-m-d') : (string) $e->date,
                    'name' => (string) $e->name,
                    'type' => $e->type,
                ])->values();

            $details = Detail::where('year', $year)->orderBy('month')->get(['id', 'month', 'bible_text', 'bible_source'])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'month' => (int) $d->month,
                    'bible_text' => (string) $d->bible_text,
                    'bible_source' => (string) $d->bible_source,
                ])->values();

            $images = $this->images->all($year);
        }

        return [
            'success' => true,
            'year' => $year,
            'defaultYear' => $this->defaultYear(),
            'calendar' => $calendar ? ['id' => $calendar->id, 'year' => (int) $calendar->year] : null,
            'versions' => Calendar::orderBy('year', 'desc')->pluck('year')->map(fn ($y) => (int) $y)->values(),
            'events' => $events,
            'details' => $details,
            'images' => $images,
            'holidayCount' => Holiday::where('year', $year)->count(),
        ];
    }

    private function fetchHolidays(int $year): void
    {
        if (Holiday::where('year', $year)->exists()) {
            return;
        }

        $json = @file_get_contents('https://feiertage-api.de/api/?jahr=' . $year . '&nur_land=HH');
        $holidays = $json ? json_decode($json, true) : null;
        if (!is_array($holidays)) {
            throw new \RuntimeException('holiday api unavailable');
        }

        foreach ($holidays as $name => $data) {
            $holiday = new Holiday();
            $holiday->year = $year;
            $holiday->date = $data['datum'];
            $holiday->name_de = $name;
            $holiday->name_cn = str_replace(
                [
                    'Neujahrstag', 'Karfreitag', 'Ostermontag', 'Tag der Arbeit', 'Christi Himmelfahrt',
                    'Pfingstmontag', 'Tag der Deutschen Einheit', 'Reformationstag', '1. Weihnachtstag', '2. Weihnachtstag',
                ],
                [
                    '元旦', '耶稣受难日', '复活节周一', '劳动节', '耶稣升天节',
                    '圣灵降临节周一', '德国国庆日', '宗教改革纪念日', '圣诞节', '圣诞节假日',
                ],
                $name
            );
            $holiday->save();
        }
    }
}
