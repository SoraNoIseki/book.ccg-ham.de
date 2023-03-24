<?php

namespace Soranoiseki\Core\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\Core\Models\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exception\NotFoundException;
use Intervention\Image\Exception\NotReadableException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /*
        $request->validate([
            'file' => 'required|mimes:png,jpeg,jpg,gif,csv,txt,xlx,xls,pdf|max:2048'
        ]);
        */

        foreach ($request->file() as $uploadFile) {
            $file = new File();
            $file->storeUploadFile($uploadFile);
            $file->save();
        }
        
        return back()->with([
            'success' => 'File saved.',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function show(File $file)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        //
    }

    /**
     * Get single uploaded image, resize available
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $src
     * @return \Illuminate\Http\Response
     */
    public function getUploadImage(Request $request, $src = '')
    {
        if (!$src || $src == '') {
            return abort(403);
        }

        // get width & height
        if ($request->has(['width', 'height'])) {
            $width = (int)$request->input('width');
            $height = (int)$request->input('height');
        } else if ($request->has('width') && !$request->has('height')) {
            $width = $request->input('width');
            $height = null;
        } else if (!$request->has('width') && $request->has('height')) {
            $width = null;
            $height = $request->input('height');
        } else {
            $width = 1200;
            $height = null;
        }
        
        // get other parameters
        $request->has('lifetime') ? $lifetime = (int)$request->input('lifetime') : $lifetime = 60;
        $request->has('quality') ? $quality = (int)$request->input('quality') : $quality = 90;
        
        try {
            $file = storage_path("app/uploads/images/") . $src;
            $cacheImage = Image::cache(function ($image) use ($file, $width, $height) {
                return $image->make($file)->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }, $lifetime, true);
        } catch (NotFoundException $e) {
            return abort(404);
        } catch (NotReadableException $e) {
            return abort(404);
        }

        return $cacheImage->response('jpg', $quality);
    }

    /**
     * Get single uploaded file
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $src
     * @return \Illuminate\Http\Response
     */
    public function getUploadFile(Request $request, $src)
    {
        $request->has('dl') ? $download = (int)$request->input('dl') : $download = false;

        try {
            $file = storage_path("app/uploads/") . $src;
            if ($download) {
                return response()->download($file);
            }
            return response()->file($file);
        } catch (FileNotFoundException $e) {
            return abort(404);
        }
    }
}
