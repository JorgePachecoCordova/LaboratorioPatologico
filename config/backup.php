<?php

return [
    'enabled' => (bool) env('BACKUP_ENABLED', false),
    'directory' => env('BACKUP_DIRECTORY', storage_path('app/private/backups')),
    'pg_dump' => env('PG_DUMP_BINARY', 'pg_dump'),
    'time' => env('BACKUP_TIME', '02:00'),
    'timeout' => (int) env('BACKUP_TIMEOUT', 3600),
];
