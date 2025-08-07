<?php

namespace Soranoiseki\BookGroup\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Soranoiseki\BookGroup\Models\Dropbox\File as DropboxFile;
use Spatie\Dropbox\Client;
use League\Flysystem\Filesystem;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Soranoiseki\BookGroup\Services\AutoRefreshingDropBoxTokenService;
use Illuminate\Support\Facades\Log;


class FetchDropboxPublicLinks extends Command
{
    private const START_FROM = "2025-01-01";
   
    private array $sundays = [];

    private Client $client;

    private Filesystem $filesystem;

    private bool $forceUpdate = false;

    private $logger;



    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:fetch-dropbox-public-links {--force-update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Dropbox public links';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->forceUpdate = $this->option('force-update');
        $this->logger = Log::channel('book');

        $start = Carbon::parse(self::START_FROM);
        $end = Carbon::now()->endOfWeek();
        $current = $start->copy()->next(Carbon::SUNDAY);
        while ($current->lte($end)) {
            $this->sundays[] = $current->copy();
            $current->addWeek();
        }
       
        $this->client = new Client(new AutoRefreshingDropBoxTokenService());
        $this->filesystem = new Filesystem(new DropboxAdapter($this->client), ['case_sensitive' => false]);
        
        $this->fetchWorshipFiles();
        $this->fetchRecordingFiles();        
    }


    private function fetchWorshipFiles() 
    {
        $this->logger->info("Fetching worship files from Dropbox...");
        
        $folder = env('DROPBOX_WORSHIP_FOLDER', '/Public/Worship/');
        foreach ($this->sundays as $date) {
            $filePath = $folder . $date->format('Y/m/Y-m-d') . '.pptx';
            
            // If the file already exists in the database and we are not forcing an update, skip it
            $exists = DropboxFile::where('file_path', $filePath)
                ->where('type', 'worship')
                ->exists();
            if ($exists && !$this->forceUpdate) {
                continue;
            }

            // If file not found in Dropbox, skip it       
            if (!$this->filesystem->fileExists($filePath)) {
                continue;
            }

            // Otherwise get the share link and create a new entry
            $shareLink = $this->getShareLink($filePath);
            if (!$shareLink) {
                continue;
            }

            $this->logger->info("Creating or updating worship file entry for: {$filePath}");
            DropboxFile::updateOrCreate(
                ['file_path' => $filePath, 'type' => 'worship'],
                [
                    'date' => $date->format('Y-m-d'),
                    'file_name' => basename($filePath),
                    'file_path' => $filePath,
                    'share_link' => $shareLink,
                    'type' => 'worship',
                ]
            );
        }
    }

    private function fetchRecordingFiles() 
    {
        $this->logger->info("Fetching recording files from Dropbox...");

        $folder = env('DROPBOX_RECORDING_FOLDER', '/Public/Recordings/');
        foreach ($this->sundays as $date) {
            $filePath = $folder . $date->format('Y/Ymd') . '.MP3';

            // If the file already exists in the database and we are not forcing an update, skip it
            $exists = DropboxFile::where('file_path', $filePath)
                ->where('type', 'recording')
                ->exists();
            if ($exists && !$this->forceUpdate) {
                continue;
            }

            // If file not found in Dropbox, skip it
            if (!$this->filesystem->fileExists($filePath)) {
                continue;
            }

            // Otherwise get the share link and create a new entry
            $shareLink = $this->getShareLink($filePath);
            if (!$shareLink) {
                continue;
            }

            $this->logger->info("Creating or updating recording file entry for: {$filePath}");
            DropboxFile::updateOrCreate(
                ['file_path' => $filePath, 'type' => 'recording'],
                [
                    'date' => $date->format('Y-m-d'),
                    'file_name' => basename($filePath),
                    'file_path' => $filePath,
                    'share_link' => $shareLink,
                    'type' => 'recording',
                ]
            );
        }
    }

    private function getShareLink($filePath) {
        $this->logger->info("Fetching share link for: {$filePath}");
        $existingLinks = $this->client->listSharedLinks($filePath);
        
        if (empty($existingLinks)) {
            $sharedLink = $this->client->createSharedLinkWithSettings($filePath);
        } else {
            $viewerLinks = array_filter($existingLinks, function ($link) use ($filePath) {
                return 
                    $link['.tag'] === 'file' &&
                    $link['name'] === basename($filePath) &&
                    $link['link_permissions']['link_access_level']['.tag'] === 'viewer';
            });
           
            $this->logger->info(sizeof($viewerLinks) . " existing links found for {$filePath}");

            if (!empty($viewerLinks)) {
                $sharedLink = $viewerLinks[0];
            } else {
                $sharedLink = $this->client->createSharedLinkWithSettings($filePath);
            }
        }

        $sharedLink = $sharedLink['url'];
        $this->logger->info("sharedLink for {$filePath}: {$sharedLink}");

        return $sharedLink ?? null;
    }

}
