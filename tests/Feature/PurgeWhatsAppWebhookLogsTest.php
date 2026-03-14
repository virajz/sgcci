<?php

declare(strict_types=1);

use App\Models\WhatsAppWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does nothing when total entries are within the limit', function () {
    WhatsAppWebhookLog::factory()->count(10)->create();

    $this->artisan('app:purge-whatsapp-webhook-logs', ['--keep' => 500])
        ->assertSuccessful()
        ->expectsOutputToContain('Nothing to purge');

    expect(WhatsAppWebhookLog::count())->toBe(10);
});

it('purges entries beyond the keep limit', function () {
    WhatsAppWebhookLog::factory()->count(600)->create();

    $this->artisan('app:purge-whatsapp-webhook-logs', ['--keep' => 500])
        ->assertSuccessful()
        ->expectsOutputToContain('Purged 100');

    expect(WhatsAppWebhookLog::count())->toBe(500);
});

it('keeps the latest entries when purging', function () {
    $logs = WhatsAppWebhookLog::factory()->count(10)->create();
    $latestId = $logs->max('id');

    $this->artisan('app:purge-whatsapp-webhook-logs', ['--keep' => 5])
        ->assertSuccessful();

    expect(WhatsAppWebhookLog::count())->toBe(5);
    expect(WhatsAppWebhookLog::orderByDesc('id')->first()->id)->toBe($latestId);
});

it('does nothing when count equals the keep limit', function () {
    WhatsAppWebhookLog::factory()->count(500)->create();

    $this->artisan('app:purge-whatsapp-webhook-logs', ['--keep' => 500])
        ->assertSuccessful()
        ->expectsOutputToContain('Nothing to purge');

    expect(WhatsAppWebhookLog::count())->toBe(500);
});
