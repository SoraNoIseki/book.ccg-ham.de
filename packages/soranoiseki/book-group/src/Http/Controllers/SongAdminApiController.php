<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Soranoiseki\BookGroup\Models\Worship\Song;
use Soranoiseki\BookGroup\Models\Worship\SongContent;
use Soranoiseki\Core\Controllers\Controller;

/** JSON API of the song management page. */
class SongAdminApiController extends Controller
{
    /** All songs (with how often each was used) and the song contents that are not in the library yet. */
    public function index(): JsonResponse
    {
        $songs = Song::raw(fn ($collection) => $collection->find([], [
            'sort' => ['name' => 1],
            'collation' => ['locale' => 'zh', 'strength' => 1],
            'projection' => ['name' => 1, 'band' => 1, 'album' => 1, 'group_id' => 1, 'checked' => 1, 'corrected' => 1],
        ]));

        $counts = SongContent::select('name', DB::raw('count(*) as total'))->groupBy('name')->pluck('total', 'name');

        $unsaved = SongContent::whereNotIn('name', $songs->pluck('name')->all())->orderBy('name')->get()
            ->map(fn (SongContent $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'band' => $c->band,
                'album' => $c->album,
                'text' => $c->text,
            ])->values();

        return response()->json([
            'songs' => $songs->map(fn (Song $s) => [
                'id' => (string) $s->_id,
                'name' => $s->name,
                'band' => $s->band,
                'album' => $s->album,
                'group_id' => $s->group_id,
                'checked' => (bool) $s->checked,
                'corrected' => (bool) $s->corrected,
                'count' => (int) ($counts[$s->name] ?? 0),
            ])->values(),
            'unsaved' => $unsaved,
        ]);
    }

    public function show(Song $song): JsonResponse
    {
        return response()->json($this->detail($song));
    }

    public function update(Request $request, Song $song): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string'],
            'checked' => ['boolean'],
            'corrected' => ['boolean'],
        ], [
            'text.required' => '歌词内容不能为空。',
        ]);

        // Normalise line breaks and stray non-breaking spaces
        $lines = array_map(
            fn ($line) => trim(str_replace("\u{A0}", '', $line)),
            preg_split('/\r\n|\r|\n/', $data['text'])
        );
        $name = $lines[0] ?? '';
        $source = $lines[1] ?? '';

        if ($name === '') {
            return response()->json(['message' => '第一行必须是诗歌名称。'], 422);
        }

        $song->name = $name;
        if ($source !== '') {
            // "乐队《专辑》"
            preg_match('/^(.*?)(《(.*?)》)?$/u', $source, $m);
            $song->band = ($m[1] ?? '') !== '' ? $m[1] : 'NA';
            $song->album = ($m[3] ?? '') !== '' ? $m[3] : 'NA';
        }

        $song->script_text_for_ppt_worker = implode("\r\n", $lines);
        $song->group_id = implode('_', [$song->name, $song->album, $song->band]);
        $song->checked = (bool) ($data['checked'] ?? false);
        $song->corrected = (bool) ($data['corrected'] ?? false);
        $song->save();

        return response()->json($this->detail($song) + ['message' => '已保存']);
    }

    public function destroy(Song $song): JsonResponse
    {
        $groupId = $song->group_id;
        $song->delete();

        return response()->json(['message' => $groupId . ' 已删除']);
    }

    /** Move a collected song content into the library. */
    public function upload(SongContent $songContent): JsonResponse
    {
        $song = new Song();
        $song->name = $songContent->name;
        $song->band = $songContent->band ?? 'NA';
        $song->album = $songContent->album ?? 'NA';
        $song->group_id = implode('_', [$song->name, $song->album, $song->band]);
        $song->script_text_for_ppt_worker = $songContent->text;

        $song->archive_date = Carbon::now()->format('Y-m-d');
        $song->archive_worker = '自动入库';
        $song->script_text_link = 'NA';
        $song->script_piano_link = 'NA';
        $song->mp3_link_dropbox = 'NA';
        $song->vedio_link_youtube = 'NA';
        $song->note = 'NA';
        $song->checked = false;
        $song->corrected = false;

        if (!$song->save()) {
            return response()->json(['message' => '入库失败。'], 500);
        }

        $songContent->link_id = (string) $song->_id;
        $songContent->save();

        return response()->json(['id' => (string) $song->_id, 'message' => '已上传：' . $song->group_id]);
    }

    private function detail(Song $song): array
    {
        $contents = SongContent::where('name', $song->name)->get()->map(fn (SongContent $c) => [
            'id' => $c->id,
            'text' => $c->text,
        ])->values();

        return [
            'song' => [
                'id' => (string) $song->_id,
                'name' => $song->name,
                'band' => $song->band,
                'album' => $song->album,
                'group_id' => $song->group_id,
                'checked' => (bool) $song->checked,
                'corrected' => (bool) $song->corrected,
                'text' => $song->script_text_for_ppt_worker,
            ],
            'contents' => $contents,
        ];
    }
}
