<?php

return [
    'uploads' => [
        'driver' => 'local',
        'root' => storage_path('app/uploads'),
        'url' => env('APP_URL') . '/uploads',
        'visibility' => 'private',
    ],
];
