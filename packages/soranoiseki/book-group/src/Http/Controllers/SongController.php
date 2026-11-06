<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\Request;
use Soranoiseki\Core\Controllers\Controller;

/**
 * Serves the song management page (role songs_management). The page is a Vue app
 * (see Resources/js/song-admin) that talks to SongAdminApiController; /songs/{song} opens
 * the editor of one song inside the same app.
 */
class SongController extends Controller
{
    public function index(Request $request, ?string $song = null)
    {
        return view('book-group::song.index', ['songId' => $song]);
    }

    /** Public lyrics list */
    public function list(Request $request)
    {
        return view('book-group::song.list');
    }
}
