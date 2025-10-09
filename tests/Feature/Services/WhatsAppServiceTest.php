<?php

declare(strict_types=1);

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // We'll check logs via file system or just verify HTTP calls
});

it('sends campaign successfully', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $service = new WhatsAppService(
        apiKey: 'test-key',
        apiUrl: 'https://api.example.com',
        username: 'TestUser',
        source: 'test-source'
    );

    $result = $service->sendCampaign(
        campaignName: 'test_campaign',
        destination: '1234567890',
        templateParams: ['$Name'],
        paramsFallbackValue: ['Name' => 'Test User']
    );

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com'
            && $request['apiKey'] === 'test-key'
            && $request['campaignName'] === 'test_campaign'
            && $request['destination'] === '1234567890'
            && $request['userName'] === 'TestUser'
            && $request['source'] === 'test-source';
    });
});

it('handles failed campaign', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Failed'], 400),
    ]);

    $service = new WhatsAppService(
        apiKey: 'test-key',
        apiUrl: 'https://api.example.com',
        username: 'TestUser',
        source: 'test-source'
    );

    $result = $service->sendCampaign(
        campaignName: 'test_campaign',
        destination: '1234567890'
    );

    expect($result)->toBeFalse();
});

it('handles exceptions', function () {
    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $service = new WhatsAppService(
        apiKey: 'test-key',
        apiUrl: 'https://api.example.com',
        username: 'TestUser',
        source: 'test-source'
    );

    $result = $service->sendCampaign(
        campaignName: 'test_campaign',
        destination: '1234567890'
    );

    expect($result)->toBeFalse();
});

it('creates service from config', function () {
    config([
        'services.whatsapp.api_key' => 'config-key',
        'services.whatsapp.api_url' => 'https://config-api.example.com',
        'services.whatsapp.username' => 'ConfigUser',
        'services.whatsapp.source' => 'config-source',
    ]);

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $service = WhatsAppService::fromConfig();
    $result = $service->sendCampaign(
        campaignName: 'test_campaign',
        destination: '1234567890'
    );

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://config-api.example.com'
            && $request['apiKey'] === 'config-key'
            && $request['userName'] === 'ConfigUser'
            && $request['source'] === 'config-source';
    });
});
