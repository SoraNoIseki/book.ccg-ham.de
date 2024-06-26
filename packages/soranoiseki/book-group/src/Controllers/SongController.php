<?php

namespace Soranoiseki\BookGroup\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\BookGroup\Models\Worship\Song;

use Soranoiseki\BookGroup\Models\Worship\SongContent;
use Soranoiseki\BookGroup\Requests\StorePowerpointRequest;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SongController extends Controller
{
    protected $content = [
        'pray',
        'preach',
        'report',
        'scripture',
        'song',
        'worker',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $songs = Song::raw(function($collection) {
            return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
        });
       
        $songContentsCount = SongContent::select('name', DB::raw('count(*) as countSong'))->groupBy('name')->get()->pluck('countSong', 'name');

        $songsInDB = Song::all()->pluck('name');
        $unsavedSongContents = SongContent::whereNotIn('name', $songsInDB)->get();

        return view('book-group::song.index', [
            'songs' => $songs,
            'songContentsCount' => $songContentsCount,
            'unsavedSongContents' => $unsavedSongContents,
        ]);
    }

    public function list(Request $request)
    {
        $songs = Song::raw(function($collection) {
            return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
        });
       
        $songContentsCount = SongContent::select('name', DB::raw('count(*) as countSong'))->groupBy('name')->get()->pluck('countSong', 'name');

        return view('book-group::song.list', [
            'songs' => $songs,
            'songContentsCount' => $songContentsCount,
        ]);
    }

    public function edit(Request $request, Song $song)
    {
        $songContents = SongContent::where('name', $song->name)->get();

        return view('book-group::song.edit', [
            'song' => $song,
            'songContents' => $songContents,
        ]);
    }

    public function save(Request $request, Song $song)
    {
        $data = $request->all();

        if (isset($data['text']) && !empty(trim($data['text']))) {
            
            // process each line
            $lines = explode(PHP_EOL, $data['text']);
            $content = [];
            foreach ($lines as $i => $line) {
                $line = str_replace(["\r", "\n", "\u{A0}"], '', $line);
                if ($i === 0) {
                    $firstLine = $line;
                } else if ($i === 1) {
                    $secondLine = $line;
                }
                $content[] = trim($line);
            }

            // get song info from text
            if ($firstLine && $firstLine !== '') {
                $song->name = $firstLine;
            }
            if ($secondLine && $secondLine !== '') {
                $pattern = '/^(.*?)(《(.*?)》)?$/u';
                preg_match($pattern, $secondLine, $matches);
                if (sizeof($matches) > 1) {
                    $song->band = $matches[1];
                    if (isset($matches[3])) {
                        $song->album = $matches[3];
                    } else {
                        $song->album = 'NA';
                    }
                } else {
                    $song->band = 'NA';
                    $song->album = 'NA';
                }
            }
        }

        $song->script_text_for_ppt_worker = implode("\r\n", $content);

        $song->group_id = implode('_', [
            $song->name,
            $song->album,
            $song->band,
        ]);

        $song->checked = isset($data['checked']) && $data['checked'] === 'on';
        $song->corrected = isset($data['corrected']) && $data['corrected'] === 'on';

        
        $song->save();
        

        return redirect()->route('book-group.song.edit', ['song' => $song->_id])->with([
            'success' => true,
            'message' => '已保存',
            'song' => $song,
        ]);
    }

    public function delete(Request $request, Song $song)
    {
        $song->delete();
        return redirect()->route('book-group.song.index')->with([
            'success' => true,
            'message' => $song->group_id . ' 已删除',
        ]);
    }

    public function upload(Request $request, SongContent $songContent)
    {
        // save song
        $song = new Song();
        $song->name = $songContent->name;
        $song->band = $songContent->band ?? 'NA';
        $song->album = $songContent->album ?? 'NA';
        $song->group_id = implode('_', [
            $song->name,
            $song->album,
            $song->band,
        ]);
        $song->script_text_for_ppt_worker = $songContent->text;

        $song->archive_date = Carbon::now()->format('Y-m-d');
        $song->archive_worker = '自动入库';
        $song->script_text_link = 'NA';
        $song->script_piano_link = 'NA';
        $song->mp3_link_dropbox = 'NA';
        $song->vedio_link_youtube = 'NA';
        $song->note = "NA";
        $song->checked = false;
        $song->corrected = false;
        
        $success = $song->save();
        
        if ($success) {
            $saved = Song::where('group_id', $song->group_id)->first();
            $songContent->link_id = $saved->_id;
            $songContent->save();
        }

        // redirect to edit page
        return redirect()->route('book-group.song.edit', ['song' => $song->_id])->with([
            'success' => true,
            'message' => '已上传: ' . $song->group_id,
        ]);
    }

    

}
