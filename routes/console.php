<?php

use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:release-expired')->hourly();
Schedule::command('bookings:send-payment-reminders')->daily();
Schedule::command('bookings:send-partial-payment-reminders --force --no-interaction')
    ->cron('0 9 */2 * *')
    ->when(fn () => now()->lte(Carbon::create(2026, 2, 28)));

// Poll CCAvenue for visitor payments that never returned from the gateway
Schedule::command('visitors:sync-pending-payments')->everyFifteenMinutes();
