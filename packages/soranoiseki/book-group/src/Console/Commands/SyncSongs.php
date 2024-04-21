<?php

namespace Soranoiseki\BookGroup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Soranoiseki\BookGroup\Models\Worship\SongContent;

class SyncSongs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:save-song-contents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save song contents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $savedSongContents = SongContent::select('file')->groupBy('file')->get()->pluck('file')->toArray();

        $allFiles = $this->getStorage()->files('content');
        $songFiles = collect($allFiles)->filter(function ($filename) use ($savedSongContents) {
            return strpos($filename, 'content/song_list_') === 0 && in_array($filename, $savedSongContents) === false;
        });

        foreach ($songFiles as $filename) {
            $fileContent = $this->getStorage()->get($filename);

            if ($fileContent && $fileContent != '') {
                $songContents = $this->splitContentText($fileContent);

                foreach ($songContents as $index => $content) {
                    $fileIndex = (int)str_replace('item', '', $index);

                    if ($content !== '') {
                        $lines = explode(PHP_EOL, $content);
                        
                        foreach ($lines as $i => $line) {
                            $line = str_replace(["\r", "\n", "\u{A0}"], '', $line);
                            if ($i === 0) {
                                $firstLine = $line;
                            } else if ($i === 1) {
                                $secondLine = $line;
                            } else {
                                break;
                            }
                        }

                        if ($firstLine && $firstLine !== '' && $secondLine && $secondLine !== '') {
                            // save song content
                            $songContent = new SongContent();
                            $songContent->file = $filename;
                            $songContent->file_index = $fileIndex;
                            $songContent->name = $firstLine;
                            
                            $pattern = '/^(.*?)(《(.*?)》)?$/u';
                            preg_match($pattern, $secondLine, $matches);
                            if (sizeof($matches) > 1) {
                                $songContent->band = $matches[1];
                                isset($matches[3]) ? $songContent->album = $matches[3] : '';
                            }

                            $songContent->text = $content;
                            $songContent->save();
                        }
                    }
                }
            }
        }
    }

    protected function getStorage() {
        return Storage::disk('pyworship');
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
