<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Soranoiseki\BookGroup\Models\Calendar\Holiday;

class FetchHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:fetch-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update balance for each transaction.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = now()->addYears(1)->year;
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
}
