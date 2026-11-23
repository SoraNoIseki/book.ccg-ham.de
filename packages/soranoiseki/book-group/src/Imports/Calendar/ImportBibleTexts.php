<?php

namespace Soranoiseki\BookGroup\Imports\Calendar;

use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Soranoiseki\BookGroup\Models\Calendar\Detail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class ImportBibleTexts implements OnEachRow, WithHeadingRow
{
    protected Calendar $calendar; 

    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        $year = $row['year'];
        $month = $row['month'];
        $bibleText = $row['text'];
        $bibleSource = $row['source'];
        
        try {
            // update
            $event = Detail::where('year', $year)->where('month', $month)->where('calendar_id', $this->calendar->id)->firstOrFail();
            $event->fill([
                'bible_text' => $bibleText,
                'bible_source' => $bibleSource,
            ])->save();
        } catch (ModelNotFoundException $e) {
            $event = Detail::create([
                'year' => $year,
                'month' => $month,
                'bible_text' => $bibleText,
                'bible_source' => $bibleSource,
                'calendar_id' => $this->calendar->id,
            ]);
        }
    }
}
