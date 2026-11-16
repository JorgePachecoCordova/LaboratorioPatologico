<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('backup.enabled')) {
    Schedule::command('patologia:backup')
        ->dailyAt(config('backup.time'))
        ->timezone(config('app.timezone'))
        ->withoutOverlapping(120);
}
