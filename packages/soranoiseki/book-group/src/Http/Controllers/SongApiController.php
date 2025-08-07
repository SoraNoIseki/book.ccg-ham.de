<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\BookGroup\Models\Worship\Song;
use Soranoiseki\Core\Traits\ApiResponser;

class SongApiController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $songs = Song::raw(function ($collection) {
            return $collection->find([], [
                'sort' => ['name' => 1],
                'collation' => ['locale' => 'zh', 'strength' => 1],
                'projection' => ['name' => 1, 'album' => 1, 'band' => 1, 'vedio_link_youtube' => 1, 'checked' => 1, 'group_id' => 1, 'vedio_link_youtube' => 1]
            ]);
        });


        return $this->respondSuccess($songs->toArray());
    }


    public function show(Request $request, string $groupId)
    {
        $song = Song::raw(function ($collection) use ($groupId) {
            return $collection->findOne(['group_id' => $groupId], [
                'sort' => ['name' => 1],
                'collation' => ['locale' => 'zh', 'strength' => 1],
                'projection' => ['_id' => 0, 'script_text_for_ppt_worker' => 1, 'group_id' => 1]
            ]);
        });
        return $this->respondSuccess([
            [
                'group_id' => $song['group_id'],
                'text' => $song['script_text_for_ppt_worker'],
            ]
        ]);
    }



}
