<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\Request;
use Soranoiseki\Core\Controllers\Controller;

use Soranoiseki\BookGroup\Models\Worship\Song;

class PowerpointAjaxController extends Controller
{
    public function getSong(Request $request)
    {
        $id = $request->id;
       
        try {
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
