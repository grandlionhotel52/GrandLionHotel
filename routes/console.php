<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hotel:backup --keep=14')
    ->dailyAt('02:30')
    ->withoutOverlapping();

Schedule::command('bookings:process-automation')
    ->hourly()
    ->withoutOverlapping();
