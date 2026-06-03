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

// Send event reminder WhatsApp notifications to confirmed visitors daily at 9:30am.
Schedule::command('visitors:send-event-reminders --no-interaction')->dailyAt('09:30');

// Purge WhatsApp webhook logs, keeping only the latest 500 entries.
Schedule::command('app:purge-whatsapp-webhook-logs')->daily();

// Mark all inside visitors as exited at end of day.
Schedule::command('visitors:mark-all-exited')->dailyAt('23:55');

// Close visitor registration for exhibitions whose end date has passed.
Schedule::command('exhibitions:close-ended-registrations')->dailyAt('23:00');

// Poll CCAvenue for visitor payments that never returned from the gateway.
// --limit=50 clears the existing backlog within a few cycles.
// --expire-hours=24 auto-fails anything CCAvenue still shows as Awaited/Unknown after 24h.
Schedule::command('visitors:sync-pending-payments --limit=50 --expire-hours=24')->everyFifteenMinutes();
