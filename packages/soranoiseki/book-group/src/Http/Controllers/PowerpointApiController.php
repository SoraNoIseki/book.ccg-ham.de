<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\BookGroup\Http\Requests\StorePowerpointRequest;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Soranoiseki\BookGroup\Models\Worship\Song;

class PowerpointApiController extends Controller
{
    protected array $contentFields = [
        'pray',
        'preach',
        'report',
        'scripture',
        'song',
        'worker',
    ];

    /**
     * GET /book-group/api/powerpoint?v=YYYY-MM-DD
     * Return PPT content for the specified version (date). If not exists, return empty content and available versions.
     */
    public function show(Request $request)
    {
        $defaultDate = Carbon::now()->endOfWeek()->format('Y-m-d');
        $date = $request->filled('v') ? $request->string('v')->toString() : $defaultDate;

        $versions = $this->getOtherVersions($date);

        $content = [];
        $versionExists = false;

        foreach ($this->contentFields as $field) {
            $filename = $field . '_list_' . $date . '.txt';
            $data = $this->readContentFile($filename);
            if ($data !== false) {
                $versionExists = true;
                $content[$field] = $this->splitContentText($data);
            }
        }

        $infoMessage = null;

        // If the current version does not exist, copy the most recent version
        if (!$versionExists && $versions->isNotEmpty()) {
            $lastVersion = $versions->first();
            foreach ($this->contentFields as $field) {
                $filename = $field . '_list_' . $lastVersion . '.txt';
                $data = $this->readContentFile($filename);
                if ($data !== false) {
                    $content[$field] = $this->splitContentText($data);
                }
            }
            $infoMessage = 'PPT内容已从版本 ' . $lastVersion . ' 复制并加载。';
        }

        return response()->json([
            'date' => $date,
            'nextWeek' => Carbon::parse($date)->addWeek()->format('Y-m-d'),
            'defaultDate' => $defaultDate,
            'content' => $content,
            'versions' => $versions->values(),
            'saveEnabled' => $defaultDate === $date,
            'infoMessage' => $infoMessage,
        ]);
    }

    /**
     * POST /api/powerpoint/save
     * Save PPT content for the specified version (date).
     */
    public function save(StorePowerpointRequest $request)
    {
        $data = $request->all();
        $this->saveContent($data);

        return response()->json([
            'success' => true,
            'message' => '已保存',
        ]);
    }

    /**
     * POST /api/powerpoint/save-download
     * Save PPT content and generate PPT, then return the file stream.
     */
    public function saveDownload(StorePowerpointRequest $request)
    {
        $data = $request->all();
        $date = $data['date'] ?? '';

        $this->saveContent($data);

        $process = new Process([
            env('PYTHON_PATH', '/usr/bin/python3'),
            storage_path('pyworship/ppt_worker.py'),
            $date
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $error = new ProcessFailedException($process);
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
            ], 500);
        }

        return $this->downloadByDate($date);
    }

    /**
     * POST /api/powerpoint/download
     * Only download: date is read from the body
     */
    public function download(StorePowerpointRequest $request)
    {
        $date = $request->input('date');
        return $this->downloadByDate($date);
    }

    /**
     * GET /api/powerpoint/songs
     * Lightweight song list for the song selector.
     */
    public function songs()
    {
        $songs = Song::raw(function ($collection) {
            return $collection->find([], [
                'sort' => ['name' => 1],
                'collation' => ['locale' => 'zh', 'strength' => 1],
                'projection' => ['name' => 1, 'album' => 1, 'checked' => 1],
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => collect($songs)->map(fn ($song) => [
                'id' => (string) $song->_id,
                'name' => $song->name,
                'album' => $song->album,
                'checked' => (bool) $song->checked,
            ])->values(),
        ]);
    }

    /**
     * GET /api/powerpoint/song?id=SONG_ID
     * @return \Illuminate\Http\JsonResponse
     */
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

    // ----------------------------
    // internal helpers
    // ----------------------------

    protected function downloadByDate(string $date)
    {
        $filename = $date . '.pptx';
        if (!$this->getStorage()->exists($filename)) {
            return response()->json([
                'success' => false,
                'message' => '无法找到文件：' . $filename,
            ], 404);
        }

        return $this->getStorage()->download($filename);
    }

    protected function saveContent(array $requestData): void
    {
        $date = $requestData['date'] ?? '';

        foreach ($this->contentFields as $field) {
            if (!array_key_exists($field, $requestData)) continue;

            $input = $requestData[$field];

            if (is_array($input)) {
                $additionalTexts = [];
                if ($field === 'preach') {
                    $additionalTexts = [1 => '引言', 2 => '经文理解与应用', 3 => '结论'];
                } elseif ($field === 'scripture') {
                    $additionalTexts = [1 => '宣召', 2 => '启应经文', 3 => '读经'];
                }

                $this->saveToContentFile($field, $date, $this->buildContentText($input, $additionalTexts));
            } else {
                $this->saveToContentFile($field, $date, $input);
            }
        }
    }

    protected function getStorage()
    {
        return Storage::disk('pyworship');
    }

    protected function getContentFiles()
    {
        return $this->getStorage()->files('content');
    }

    protected function putContentFile($filename, $content)
    {
        return $this->getStorage()->put('content/' . $filename, $content);
    }

    protected function readContentFile($filename)
    {
        $filePath = 'content/' . $filename;
        if ($this->getStorage()->exists($filePath)) {
            return $this->getStorage()->get($filePath);
        }
        return false;
    }

    protected function saveToContentFile(string $contentType, string $date, string|array $content): void
    {
        if ($contentType === '' || $date === '') return;

        $filename = $contentType . '_list_' . $date . '.txt';
        $this->putContentFile($filename, $content);
    }

    protected function getOtherVersions(string $currentDate)
    {
        $contentFiles = $this->getContentFiles();
        $dates = collect();

        foreach ($contentFiles as $filePath) {
            $date = $this->getDateFromFilePath($filePath);
            if ($date !== '' && $date !== $currentDate && !$dates->contains($date)) {
                $dates[] = $date;
            }
        }

        return $dates->sortDesc();
    }

    protected function getDateFromFilePath(string $filePath): string
    {
        $filename = explode('/', $filePath);
        $filename = end($filename);

        $pattern = '/.*_(\d{4}-\d{2}-\d{2})/';
        preg_match($pattern, $filename, $matches);

        return (count($matches) > 1) ? $matches[1] : '';
    }

    protected function buildContentText(array $inputs, array $additionalTexts = []): string
    {
        $return = [];

        foreach ($inputs as $key => $value) {
            if (trim((string)$value) === '') continue;

            $index = (int)str_replace('item', '', (string)$key);
            if (!empty($additionalTexts) && isset($additionalTexts[$index])) {
                $return[] = '#' . (string)$index . '(' . $additionalTexts[$index] . ')';
            } else {
                $return[] = '#' . (string)$index;
            }
            $return[] = trim((string)$value);
        }

        return implode("\r\n", $return);
    }

    protected function splitContentText(string $contentText): array
    {
        $return = [];

        $lines = explode(PHP_EOL, $contentText);
        $index = '';
        $buildTexts = [];

        foreach ($lines as $line) {
            $line = str_replace(["\r", "\n", "\u{A0}"], '', $line);

            $pattern = '/^#(\d{1,2}).*/';
            preg_match($pattern, $line, $matches);

            if (count($matches) > 1) {
                if ($index === '') {
                    $index = 'item' . $matches[1];
                } else {
                    $return[$index] = implode("\r\n", $buildTexts);
                    $index = 'item' . $matches[1];
                    $buildTexts = [];
                }
                continue;
            }

            $buildTexts[] = trim($line);
        }

        if ($index !== '') {
            $return[$index] = implode("\r\n", $buildTexts);
        }

        return $return;
    }
}