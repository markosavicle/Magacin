<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs a scheduler process: `php artisan schedule:work`, or cron running `php artisan schedule:run` every minute.
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('partners:verify-balances')->dailyAt('02:30')->withoutOverlapping();
