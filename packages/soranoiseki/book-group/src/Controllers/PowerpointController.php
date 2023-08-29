<?php

namespace Soranoiseki\BookGroup\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\Library\Models\Book;
use Soranoiseki\Library\Models\Member;
use Maatwebsite\Excel\Facades\Excel;
use Soranoiseki\Library\Imports\ImportBooks;
use Soranoiseki\Library\Models\Copy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\File;
use Soranoiseki\BookGroup\Requests\StorePowerpointRequest;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PowerpointController extends Controller
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
    public function index()
    {
        $date = Carbon::now()->nextWeekendDay()->format('Y-m-d');

        $contentFiles = $this->getContentFiles();
        $content = [];
        foreach ($this->content as $field) {
            $filename = $field . '_list_' . $date . '.txt';
            $data = $this->readContentFile($filename);
            if ($data) {
                $content[$field] = $data;
            }
        }

        return view('book-group::powerpoint.index', [
            'date' => Carbon::now()->nextWeekendDay()->format('Y-m-d'),
            'content' => $content
        ]);
    }

    public function store(StorePowerpointRequest $request)
    {
        // save data
        $validated = $request->validated();
        $date = $validated['date'];
        foreach ($this->content as $field) {
            if (isset($validated[$field])) {
                $this->saveToContentFile($field, $date, $validated[$field]);
            }
        }

        // redirect back
        return redirect()->route('book-group.ppt.index')->with([
            'success' => true,
            'message' => '已保存'
        ]);
    }


    /**
     * Create a powerpoint 
     *
     */
    public function download(StorePowerpointRequest $request)
    {
        // save data
        $validated = $request->validated();
        $date = $validated['date'];
        foreach ($this->content as $field) {
            if (isset($validated[$field])) {
                $this->saveToContentFile($field, $date, $validated[$field]);
            }
        }

        // run python
        $process = new Process([env('PYTHON_PATH', '/usr/bin/python3'), storage_path('pyworship/ppt_worker.py'), $date]);
        $process->run();

        if (!$process->isSuccessful()) {
            $error =  new ProcessFailedException($process);
            return redirect()->route('book-group.ppt.index')->with([
                'success' => false,
                'message' => $error->getMessage()
            ]);
        }
      
        // download PPT file
        $filename = $date . '.pptx';
        if (!$this->getStorage()->exists($filename)) {
            return redirect()->route('book-group.ppt.index')->with([
                'success' => false,
                'message' => '无法找到文件：' . $filename
            ]);
        }
        
        return $this->getStorage()->download($filename);
    }


    protected function getStorage() {
        return Storage::disk('pyworship');
    }

    protected function getContentFiles() {
        return $this->getStorage()->files('content');
    }

    protected function putContentFile($filename, $content) {
        return $this->getStorage()->put('content/' . $filename, $content);
    }

    protected function readContentFile($filename) {
        $filePath = 'content/' . $filename;
        if ($this->getStorage()->exists($filePath)) {
            return $this->getStorage()->get($filePath);
        }
        return false;
    }

    protected function saveToContentFile(string $contentType, string $date, string $content) {
        if (!$contentType || $contentType == '' || !$date || $date == '') {
            return;
        }

        $filename = $contentType . '_list_' . $date . '.txt';
        $this->putContentFile($filename, $content);
    }

}
