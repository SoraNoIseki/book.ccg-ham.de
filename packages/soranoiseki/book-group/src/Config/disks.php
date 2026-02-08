<?php

return [
    'pyworship' => [
        'driver' => 'local',
        'root' => storage_path('pyworship'),
        'throw' => false,
    ],
    'dropbox' => [
        'driver' => 'dropbox',
        'authorization_token' => env('DROPBOX_AUTH_TOKEN'),
    ],
    'scripts' => [
        'driver' => 'local',
        'root' => storage_path('scripts'),
        'throw' => false,
    ],
];
