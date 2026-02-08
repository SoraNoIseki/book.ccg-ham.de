<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Soranoiseki\BookGroup\Models\Calendar\Holiday;
use Soranoiseki\BookGroup\Models\Calendar\Detail;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Overtrue\ChineseCalendar\Calendar as ChineseCalendar;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Soranoiseki\BookGroup\Imports\Calendar\ImportEvents;
use Soranoiseki\BookGroup\Imports\Calendar\ImportBibleTexts;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;


class CalendarController extends Controller
{
    protected $chineseCalendar;
    protected $holidays;
    protected $events;
    protected $details;
    protected $days;
    protected $defaultYear;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->chineseCalendar = new ChineseCalendar();
        $this->defaultYear = (string)((int)date('Y') + 1);
    }

    /******************
     * Actions
     ******************/

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View
     */
    public function index(Request $request) {
        $year = $request->has('year') ? $request->year : $this->defaultYear;
        $calendar = Calendar::with(['events', 'details'])->where('year', $year)->first();
        $events = Event::where('year', $year)->orderBy('date', 'asc')->get();

        return view('book-group::calendar.index', [
            'calendar' => $calendar,
            'events' => $events,
        ]);
    }

    /**
     * Show single calendar record with id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Calendar $calendar) {
        return response()->json([
            'success' => true,
            'data' => [
                'calendar' => $calendar,
            ]
        ]);
       
    }

    /**
     * Show single calendar record with year
     *
     * @param $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByYear(int $year) {
        if ($year && $year > 0) {
            try {
                $calendar = Calendar::where('year', '=', $year)->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'calendar' => $calendar,
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid year.'
            ]);
        }
    }

    /**
     * Create a new calendar
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request) {
        $year = $request->has('year') ? $request->year : $this->defaultYear;

        // create a new calendar version
        $calendar = Calendar::where('year', $year)->first();
        if (!$calendar) {
            $calendar = new Calendar();
            $calendar->year = $year;
            $calendar->save();

            // fetch holidays
            $this->getHolidaysFromAPI($year);

            Session::flash('success', '成功创建日历' . $year);
        } else {
            Session::flash('error', '日历' . $year . '已经存在');
        }
        
        return Response::redirectToRoute('book-group.calendar.index');
    }

    public function update(Request $request, Calendar $calendar) {
        if ($request->has('year')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbid updating year!'
            ]);
        }

        try {
            $calendar = Calendar::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($request->has('settings')) {
            $calendar->settings = $request->settings;
        }
        if ($request->has('template')) {
            $calendar->template = $request->template;
        }
        if ($request->has('styles')) {
            $calendar->styles = $request->styles;
        }

        $calendar->save();
        $calendar = Calendar::find($id);

        return response()->json([
            'success' => true,
            'data' => [
                'calendar' => $calendar,
            ]
        ]);
    }


    public function delete(Request $request, Calendar $calendar) {
        try {
            $calendar = Calendar::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        $calendar->delete();
        $versions = $this->getActiveVersions();

        return response()->json([
            'success' => true,
            'message' => 'Calendar ' . $id . ' has deleted.',
            'data' => [
                'versions' => $versions,
            ]
        ]);
    }


    /******************
     * Calendar years
     ******************/

    /**
     * Get all years
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVersions() {
        $versions = $this->getActiveVersions();
        return response()->json([
            'success' => true,
            'data' => [
                'versions' => $versions,
            ]
        ]);
    }

    public function generatePreview(Request $request, Calendar $calendar) {
        // prepare data
        $this->prepareEvents($calendar->year);
        $this->prepareHolidays($calendar->year);
        $this->prepareDetails($calendar->year);
        $this->prepareDays($calendar->year); // prepare days needs data above

        // build PDF data
        $data = $this->buildPdfData($calendar);

        // response
        return view('book-group::calendar.pdf', $data);
    }

    public function downloadPdf(Request $request, Calendar $calendar) {
        // prepare data
        $this->prepareEvents($calendar->year);
        $this->prepareHolidays($calendar->year);
        $this->prepareDetails($calendar->year);
        $this->prepareDays($calendar->year); // prepare days needs data above

        // build PDF data
        $data = $this->buildPdfData($calendar);
        $html = view('book-group::calendar.pdf', $data)->render();

// dd($html);

        $year = $calendar->year;
        $tempHtmlPath = storage_path('scripts/temp_calendar_' . $year . '.html');
        $tempPdfPath = storage_path('scripts/temp_calendar_' . $year . '.pdf');

        // save html to a temp file
        file_put_contents($tempHtmlPath, $html);

        // dd($tempHtmlPath);


        $pythonPath = env('COMMON_PYTHON_PATH', '/usr/bin/python3');
        $scriptPath = storage_path('scripts/html_to_pdf.py');
        
        // run python
        $process = new Process([
            $pythonPath,
            $scriptPath,
            $tempHtmlPath,
            $tempPdfPath,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
        $error = new ProcessFailedException($process);

            // If failed, clean up temp files
            @unlink($tempHtmlPath);
            @unlink($tempPdfPath);

            return redirect()
                ->route('book-group.calendar.index', ['v' => $year])
                ->with([
                    'success' => false,
                    'message' => $error->getMessage(),
                ]);
        }
      
        // Read the generated PDF file
        $pdfContent = file_get_contents($tempPdfPath);

        // Clean up after use
        @unlink($tempHtmlPath);
        @unlink($tempPdfPath);

        // Return PDF
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="calendar_' . $year . '.pdf"');
    }


    /******************
     * Private methods
     ******************/

    private function getActiveVersions() {
        return Calendar::orderBy('year', 'desc')
            ->orderBy('version', 'desc')
            ->get(['id', 'year', 'version', 'created_at', 'updated_at']);
    }

    private function prepareDays($year) {
        $this->days = array();
        $this->days[0] = $this->buildMonth($year-1, 12);
        for($month=1; $month<=12; $month++) {
            $this->days[(string)$month] = $this->buildMonth($year, $month);
        }
        $this->days[13] = $this->buildMonth($year+1, 1);
    }

    private function prepareEvents($year) {
        $this->events = array();
       
        // get yearly events from database
        $events = Event::where('year', '=', $year)->get();
        foreach ($events->toArray() as $event) {
            $event['name'] = str_replace("|", "<br>", $event['name']);
            $this->events[$event['date']][] = $event;
        }
    }

    /**
     * @param int $year
     * @param int $month
     * @param string $date
     * @param string $name
     * @param string $type
     */
    private function insertEvent($year, $month, $date, $name, $type = "") {
        $yearMonth = (string)$year . "-" . (string)$month;
        $date = strtotime($date . " of " . $yearMonth);
        $dateString = date("Y-m-d", $date);

        if ((int)date('m', $date) != $month) {
            return;
        }

        $this->events[$dateString][] = [
            "year" => $year,
            "date" => $dateString,
            "name" => $name,
            "type" => $type
        ];
    }

    private function prepareDetails($year) {
        $details = Detail::where('year', '=', $year)->get();
        $this->details = array();
        foreach ($details as $data) {
            $this->details[$data['month']] = $data;
        }
    }

    private function prepareHolidays($year) {
        $holidays = Holiday::where('year', '=', $year)->get();
        $this->holidays = array();
        foreach ($holidays as $holiday) {
            $this->holidays[$holiday['date']] = $holiday;
        }
    }

    private function getHolidaysFromAPI($year) {
        $holidays = Holiday::where('year', '=', $year)->get();
        if (!sizeof($holidays)) {
            $holidays = file_get_contents('https://feiertage-api.de/api/?jahr=' . (string)$year . '&nur_land=HH');
            $holidays = json_decode($holidays, true);
            foreach ($holidays as $name => $data) {
                $holiday = new Holiday();
                $holiday->year = $year;
                $holiday->date = $data['datum'];
                $holiday->name_de = $name;
                $holiday->name_cn = $this->translateHoliday($name);
                $holiday->save();
            }
        }
    }

    private function translateHoliday($name) {
        return str_replace(
            [
                'Neujahrstag', 'Karfreitag', 'Ostermontag', 'Tag der Arbeit', 'Christi Himmelfahrt',
                'Pfingstmontag', 'Tag der Deutschen Einheit', 'Reformationstag', '1. Weihnachtstag', '2. Weihnachtstag'
            ], [
                '元旦', '耶稣受难日', '复活节周一', '劳动节', '耶稣升天节',
                '圣灵降临节周一', '德国国庆日', '宗教改革纪念日', '圣诞节', '圣诞节假日'
            ], $name
        );
    }   
    

    /**
     * @param Calendar $calendar
     */
    private function buildPdfData(Calendar $calendar) {
       
        $path = 'downloads';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $filename = $path . '/' . implode('-', [
            'calendar',
            $calendar->year,
            'v' . $calendar->version,
            date("Ymd-His")
        ]) . '.pdf';

        $month = [
            'Januar',
            'Februar',
            'März',
            'April',
            'Mai',
            'Juni',
            'Juli',
            'August',
            'September',
            'Oktober',
            'November',
            'Dezember'
        ];
        
        $data = [
            'calendar' => $calendar,
            'filename' => $filename,
            'details' => $this->details,
            'days' => $this->days,
            'year' => $calendar->year,
            'monthText' => $month,
        ];
       
        return $data;
    }


    /**
     * @param int $year
     * @param int $month
     * @return array
     */
    private function buildMonth(int $year, int $month)
    {
        $days = array();
        $countDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $index = 1;

        for ($day = 1; $day <= $countDays; $day++) {
            $date = mktime(0, 0, 0, $month, $day, $year);

            // last month
            if ($day == 1) {
                $dayOfWeek = date('w', $date);
                if ($dayOfWeek == 0) { // sunday
                    $dayOfWeek = 7;
                }

                for ($i = (int)$dayOfWeek - 1; $i >= 1; $i--) {
                    $dayOfLastMonth = strtotime(date('Y-m-d', $date) . ' - ' . (string)$i .' day');
                    $days[] = $this->buildDayItem($dayOfLastMonth, 'prev-month');
                    $index++;
                }
            }

            // this month
            $days[] = $this->buildDayItem($date, 'current-month');
            $index++;

            // next month
            if ($day == $countDays) {
                $dayOfWeek = date('w', $date);
                if ($dayOfWeek == 0) { // sunday
                    $dayOfWeek = 7;
                }

                for ($i = 1; $i <= 7 - (int)$dayOfWeek; $i++) {
                    $dayOfNextMonth = strtotime(date('Y-m-d', $date) . ' + ' . (string)$i .' day');
                    $days[] = $this->buildDayItem($dayOfNextMonth, 'next-month');
                    $index++;
                }
            }
        }
        // print_r($days);
        return $days;
    }

    /**
     * @param int $date
     * @param string $class
     * @return array
     */
    private function buildDayItem(int $date, string $class = '')
    {
        $dayWithoutLeadingZero = date('j', $date);
        $date = date('Y-m-d', $date);
        [$year, $month, $day] = explode('-', $date);

        return array(
            'date' => $date,
            'day' => $dayWithoutLeadingZero,
            'details' => $this->chineseCalendar->solar((int)$year, (int)$month, (int)$day),
            'class' => $class,
            'data' => [
                'events' => key_exists($date, $this->events) ? $this->events[$date] : null,
                'holiday' => key_exists($date, $this->holidays) ? $this->holidays[$date] : null,
            ],
        );
    }


    private function weekOfYear($date) {
        $weekOfYear = intval(date("W", $date));
        if (date('n', $date) == "1" && $weekOfYear > 51) {
            // It's the last week of the previos year.
            return 0;
        }
        else if (date('n', $date) == "12" && $weekOfYear == 1) {
            // It's the first week of the next year.
            return 53;
        }
        else {
            // It's a "normal" week.
            return $weekOfYear;
        }
    }

    private function weekOfMonth($date) {
        //Get the first day of the month.
        $firstOfMonth = strtotime(date("Y-m-01", $date));
        //Apply above formula.
        return $this->weekOfYear($date) - $this->weekOfYear($firstOfMonth) + 1;
    }

    public function importEvents(Request $request, Calendar $calendar)
    {
        if ($calendar) {
            Event::where('calendar_id', $calendar->id)->delete();
            Excel::import(new ImportEvents($calendar), $request->file('file'));
        }
        return Response::redirectToRoute('book-group.calendar.index');
    }

    public function importBibleTexts(Request $request, Calendar $calendar)
    {
        if ($calendar) {
            Excel::import(new ImportBibleTexts($calendar), $request->file('file'));
        }
        return Response::redirectToRoute('book-group.calendar.index');
    }

    public function updateHolidays(Request $request)
    {
        $year = $this->defaultYear;
        $this->getHolidaysFromAPI($year);
        return Response::redirectToRoute('book-group.calendar.index');
    }


}
