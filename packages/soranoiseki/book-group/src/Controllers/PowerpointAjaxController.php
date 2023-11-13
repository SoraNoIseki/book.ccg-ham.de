<?php

namespace Soranoiseki\BookGroup\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Soranoiseki\Core\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Soranoiseki\BookGroup\Requests\StorePowerpointRequest;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Soranoiseki\BookGroup\Models\Worship\Song;

class PowerpointAjaxController extends Controller
{
    public function getSong(Request $request)
    {
        $id = $request->id;
       
        try {
            $songs = Song::query()->get();
            return response()->json([
                'success' => true,
                'data' => Song::where('_id', $id)->first()
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage()
            ]);
        }
    }


}
