<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dijalankan oleh cron `php artisan schedule:run` yang dijelaskan di README.
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('activitylog:clean')->weeklyOn(1, '03:30')->withoutOverlapping();
Schedule::command('cms:check-update')->twiceDaily(4, 16)->withoutOverlapping();
Schedule::command('cms:process-update-queue')->everyMinute()->withoutOverlapping(15);
Schedule::command('cms:process-update-queue --auto')->dailyAt('02:30')->withoutOverlapping(15);
