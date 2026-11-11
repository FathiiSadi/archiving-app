<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Regular data backups (BR-17). Needs the scheduler: `* * * * * php artisan schedule:run` on the server.
Schedule::command('dal:backup')->dailyAt(config('backup.time'))->withoutOverlapping();
