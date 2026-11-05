<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Soranoiseki\Core\Controllers\Controller;

/**
 * Serves the calendar page. The page is a Vue app (see Resources/js/calendar) that talks to
 * CalendarApiController; the PDF is rendered by storage/scripts/calendar/calendar_pdf.py.
 */
class CalendarController extends Controller
{
    public function index()
    {
        return view('book-group::calendar.index');
    }
}
