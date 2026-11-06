<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Soranoiseki\Core\Controllers\Controller;

/**
 * Serves the PPT page. The page is a Vue app (see Resources/js/ppt) that talks to
 * PowerpointApiController.
 */
class PowerpointController extends Controller
{
    public function index()
    {
        return view('book-group::powerpoint.index');
    }
}
