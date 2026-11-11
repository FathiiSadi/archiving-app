<?php

return [

    // Filesystem disk that receives backups. Must be private: backups hold financial data and
    // password hashes. On hosts with a temporary local disk, point this at an S3-compatible disk.
    'disk' => env('BACKUP_DISK', 'backups'),

    // Number of daily backups to keep.
    'keep' => (int) env('BACKUP_KEEP', 30),

    // Time of the daily backup (app timezone).
    'time' => env('BACKUP_TIME', '02:30'),

];
