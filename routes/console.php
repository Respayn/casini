<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('metrika:sync-search-engines-goals')->dailyAt('03:00');
Schedule::command('metrika:sync-utm-goals')->dailyAt('03:30');
Schedule::command('metrika:sync-conversions-goals')->dailyAt('04:00');
