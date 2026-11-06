<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Soranoiseki\Core\Controllers\Controller;

/**
 * Serves the library page. The page is a Vue app (see Resources/js/library) that talks to
 * LibraryApiController.
 */
class LibraryController extends Controller
{
    public function index()
    {
        return view('book-group::library.index');
    }
}
