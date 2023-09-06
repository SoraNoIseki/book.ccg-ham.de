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
    public function index(Request $request)
    {
        if ($request->has('v') && $request->get('v') != '') {
            $date = $request->get('v');
        } else {
            $date = Carbon::now()->endOfWeek()->format('Y-m-d');
        }
        
        $content = [];
        foreach ($this->content as $field) {
            $filename = $field . '_list_' . $date . '.txt';
            $data = $this->readContentFile($filename);
            if ($data) {
                $content[$field] = $this->splitContentText($data);
            }
        }

        return view('book-group::powerpoint.index', [
            'date' => $date,
            'content' => $content,
            'versions' => $this->getOtherVersions($date),
        ]);
    }

    public function store(StorePowerpointRequest $request)
    {
        // save data
        $validated = $request->validated();
        $date = $validated['date'];
        $this->saveContent($validated);

        // redirect back
        return redirect()->route('book-group.ppt.index', ['v' => $date])->with([
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
        $this->saveContent($validated);

        // run python
        $process = new Process([env('PYTHON_PATH', '/usr/bin/python3'), storage_path('pyworship/ppt_worker.py'), $date]);
        $process->run();

        if (!$process->isSuccessful()) {
            $error =  new ProcessFailedException($process);
            return redirect()->route('book-group.ppt.index', ['v' => $date])->with([
                'success' => false,
                'message' => $error->getMessage()
            ]);
        }
      
        // download PPT file
        $filename = $date . '.pptx';
        if (!$this->getStorage()->exists($filename)) {
            return redirect()->route('book-group.ppt.index', ['v' => $date])->with([
                'success' => false,
                'message' => '无法找到文件：' . $filename
            ]);
        }
        
        return $this->getStorage()->download($filename);
    }


    protected function saveContent(array $validated) {
        $date = $validated['date'];
        foreach ($this->content as $field) {
            if (isset($validated[$field])) {
                $input = $validated[$field];

                if (is_array($input)) {
                    $additionalTexts = [];
                    if ($field == 'preach') {
                        $additionalTexts = [1 => '引言', 2 => '经文理解与应用', 3 => '结论'];
                    } else if ($field == 'scripture') {
                        $additionalTexts = [1 => '宣召', 2 => '启应经文', 3 => '读经'];
                    }
                    $this->saveToContentFile($field, $date, $this->buildContentText($input, $additionalTexts));
                } else {
                    $this->saveToContentFile($field, $date, $input);
                }
            }
        }
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

    protected function saveToContentFile(string $contentType, string $date, string | array $content) {
        if (!$contentType || $contentType == '' || !$date || $date == '') {
            return;
        }

        $filename = $contentType . '_list_' . $date . '.txt';
        $this->putContentFile($filename, $content);
    }

    protected function getOtherVersions(string $currentDate) { 
        // get content files
        $contentFiles = $this->getContentFiles();
        $dates = collect();
        foreach ($contentFiles as $filePath) {
            $date = $this->getDateFromFilePath($filePath);
            if ($date !== '' && $date !== $currentDate && !$dates->contains($date)) {
                $dates[] = $date;
            }
        }

        return $dates->sortDesc();;
    }

    protected function getDateFromFilePath(string $filePath) {
        $filename = explode('/', $filePath);
        $filename = end($filename);

        $pattern = '/.*_(\d{4}-\d{2}-\d{2})/';
        preg_match($pattern, $filename, $matches);

        if (sizeof($matches) > 1) {
            return $matches[1];
        }
        return '';
    }


    protected function buildContentText(array $inputs, array $additionalTexts = []) {
        $return = [];
        foreach ($inputs as $key => $value) {
            if (trim($value) == '') {
                continue;
            }

            $index = (int)str_replace('item', '', $key);
            $additionalText = '';
            if (!empty($additionalTexts) && isset($additionalTexts[$index])) {
                $additionalText = $additionalTexts[$index];
                $return[] = '#' . (string)$index . '(' . $additionalText . ')';
            } else {
                $return[] = '#' . (string)$index;
            }
            
            $return[] = trim($value);
        }
        return implode("\r\n", $return);
    }

    protected function splitContentText(string $contentText) {
        $return = [];

        $lines = explode(PHP_EOL, $contentText);
        $index = '';
        $buildTexts = [];
        foreach ($lines as $line) {
            $line = str_replace(["\r", "\n", "\u{A0}"], '', $line);

            $pattern = '/^#(\d{1,2}).*/';
            preg_match($pattern, $line, $matches);
            if (sizeof($matches) > 1) {
                if ($index == '') {
                    $index = 'item' . $matches[1];
                } else {
                    $return[$index] = implode("\r\n", $buildTexts);
                    $index = 'item' . $matches[1];
                    $buildTexts = [];
                }
                continue;
            } else {
                $buildTexts[] = trim($line);
            }
        }
        $return[$index] = implode("\r\n", $buildTexts);

        return $return;
    }

}
