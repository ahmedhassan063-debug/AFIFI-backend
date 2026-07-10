<?php

return [
    'max_size_bytes' => (int) env('MEDIA_MAX_SIZE_BYTES', 10 * 1024 * 1024),

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
        'video/mp4',
        'video/webm',
    ],

    'allowed_disks' => [
        'public',
        'local',
    ],
];
