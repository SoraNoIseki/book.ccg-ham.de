<?php

namespace Soranoiseki\BookGroup\Http\Controllers\Api;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Soranoiseki\BookGroup\Models\TaskPlan\TopicInfo;
use Soranoiseki\BookGroup\Models\TaskPlan\PreacherInfo;
use Soranoiseki\BookGroup\Models\Dropbox\File as DropboxFile;


class WebsiteApiController extends BaseApiController
{
    public function __construct()
    {

    }

    public function getPlansByYear(Request $request, $year)
    {
        $now = Carbon::now();
        $currentYear = $now->year;

        // Determine last month to include
        $lastMonth = ($year == $currentYear) ? $now->month : 12;

        // Build group_id list like "2025_1", "2025_2", ...
        $groupIds = [];
        for ($month = 1; $month <= $lastMonth; $month++) {
            $groupIds[] = "{$year}_{$month}";
        }

        // Fetch TopicInfo data
        $topics = TopicInfo::raw(function ($collection) use ($groupIds) {
            return $collection->find([
                'group_id' => ['$in' => $groupIds],
            ]);
        });

        // Fetch PreacherInfo data
        $preachers = PreacherInfo::raw(function ($collection) use ($groupIds) {
            return $collection->find([
                'group_id' => ['$in' => $groupIds],
            ]);
        });

        // Build quick lookup maps: group_id => record
        $topicMap = [];
        foreach ($topics as $topic) {
            $topicMap[$topic['group_id']] = $topic;
        }

        $preacherMap = [];
        foreach ($preachers as $preacher) {
            $preacherMap[$preacher['group_id']] = $preacher;
        }

        $results = [];

        // Loop through months and weeks
        for ($month = 1; $month <= $lastMonth; $month++) {
            $groupId = "{$year}_{$month}";
            $topic = $topicMap[$groupId] ?? null;
            $preacher = $preacherMap[$groupId] ?? null;

            for ($i = 1; $i <= 5; $i++) {
                $weekKey = 'week' . $i;

                // Calculate the Sunday of this week
                $sunday = Carbon::createFromDate($year, $month, 1)
                    ->startOfMonth()
                    ->addWeeks($i - 1)
                    ->endOfWeek(Carbon::SUNDAY);

                // Skip if this Sunday is outside the current month
                if ($sunday->month != $month) {
                    continue;
                }

                $topicData = explode('+', $topic[$weekKey] ?? '');
                $results[] = [
                    'week' => $sunday->isoWeek(),
                    'date' => $sunday->toDateString(),
                    'topic' => isset($topicData[0]) ? trim($topicData[0]) : '',
                    'text' => isset($topicData[1]) ? trim($topicData[1]) : '',
                    'preacher' => $preacher[$weekKey] ?? '',
                    'worship_file' => DropboxFile::where('date', $sunday->toDateString())
                        ->where('type', 'worship')
                        ->get()
                        ->map(function ($file) {
                            return [
                                'file_name' => $file->file_name,
                                'share_link' => $file->share_link,
                            ];
                        })->first(),
                    'recording_file' => DropboxFile::where('date', $sunday->toDateString())
                        ->where('type', 'recording')
                        ->get()
                        ->map(function ($file) {
                            return [
                                'file_name' => $file->file_name,
                                'share_link' => $file->share_link,
                            ];
                        })->first(),
                ];
            }
        }

        // Sort by date descending
        usort($results, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        return $this->respondWithData([
            'year' => (int) $year,
            'plans' => $results,
        ]);
    }

}
