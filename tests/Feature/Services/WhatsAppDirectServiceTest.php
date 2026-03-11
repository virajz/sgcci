<?php

declare(strict_types=1);

use App\Services\WhatsAppDirectService;
use Illuminate\Support\Facades\Http;

function makeDirectService(): WhatsAppDirectService
{
    return new WhatsAppDirectService(
        projectId: 'test-project-id',
        apiKey: 'test-api-key',
        testNumber: '917874949091',
    );
}

it('sends a text message successfully', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $result = makeDirectService()->sendText('917874949091', 'Hello there');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'test-project-id/messages')
            && $request['to'] === '917874949091'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Hello there';
    });
});

it('sends an image message successfully', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-2']]], 200)]);

    $result = makeDirectService()->sendImage('917874949091', 'https://example.com/image.jpg', 'Test caption');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['type'] === 'image'
            && $request['image']['link'] === 'https://example.com/image.jpg'
            && $request['image']['caption'] === 'Test caption';
    });
});

it('sends an image message without caption', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-3']]], 200)]);

    $result = makeDirectService()->sendImage('917874949091', 'https://example.com/image.jpg');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['type'] === 'image'
            && ! isset($request['image']['caption']);
    });
});

it('sends a document message successfully', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-4']]], 200)]);

    $result = makeDirectService()->sendDocument('917874949091', 'https://example.com/file.pdf', 'invoice', 'See attached');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['type'] === 'document'
            && $request['document']['link'] === 'https://example.com/file.pdf'
            && $request['document']['filename'] === 'invoice'
            && $request['document']['caption'] === 'See attached';
    });
});

it('strips leading plus and spaces from phone number', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-5']]], 200)]);

    makeDirectService()->sendText('+91 787 494 9091', 'Hi');

    Http::assertSent(fn ($request) => $request['to'] === '917874949091');
});

it('returns false on API failure', function () {
    Http::fake(['*' => Http::response(['error' => 'Bad request'], 400)]);

    $result = makeDirectService()->sendText('917874949091', 'Hello');

    expect($result)->toBeFalse();
});

it('returns false on exception', function () {
    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $result = makeDirectService()->sendText('917874949091', 'Hello');

    expect($result)->toBeFalse();
});

it('creates service from config', function () {
    config([
        'services.whatsapp_direct.project_id' => 'cfg-project',
        'services.whatsapp_direct.api_key' => 'cfg-key',
        'services.whatsapp_direct.test_number' => '917874949091',
    ]);

    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-6']]], 200)]);

    $service = WhatsAppDirectService::fromConfig();
    $result = $service->sendText('917874949091', 'Config test');

    expect($result)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'cfg-project/messages')
        && $request->hasHeader('X-API-WA-Project-API-Pwd', 'cfg-key')
    );
});

it('returns the configured test number', function () {
    $service = makeDirectService();

    expect($service->testNumber())->toBe('917874949091');
});
