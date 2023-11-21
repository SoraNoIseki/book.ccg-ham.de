<?php

namespace Soranoiseki\BookGroup\Imports\Calendar;

use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class ImportEvents implements OnEachRow, WithHeadingRow
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

        $date = Carbon::parse($row['date']);
        $name = $row['event'];
        
        try {
            // update
            $event = Event::where('date', $date->format('Y-m-d'))->where('calendar_id', $this->calendar->id)->firstOrFail();
            $event->fill([
                'event' => $name,
            ])->save();
        } catch (ModelNotFoundException $e) {
            $event = Event::create([
                'year' => $this->calendar->year,
                'date' => $date->format('Y-m-d'),
                'name' => trim($name),
                'type' => 'yearly',
                'calendar_id' => $this->calendar->id,
            ]);
        }
    }
}
