<?php

declare(strict_types=1);

use App\Jobs\SendWhatsAppCampaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('dispatches job with correct parameters', function () {
    SendWhatsAppCampaign::dispatch(
        campaignName: 'booking_received',
        phoneCode: '+91',
        phoneNumber: '9876543210',
        templateParams: ['John Doe', 'Auto Expo', 'A1, A2'],
    );

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->campaignName === 'booking_received'
            && $job->phoneCode === '+91'
            && $job->phoneNumber === '9876543210'
            && $job->templateParams === ['John Doe', 'Auto Expo', 'A1, A2'];
    });
});

it('formats phone number correctly when handling job', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $job = new SendWhatsAppCampaign(
        campaignName: 'booking_received',
        phoneCode: '+91',
        phoneNumber: '78749 49091',
        templateParams: ['John Doe']
    );

    $job->handle();

    Http::assertSent(function ($request) {
        return $request['destination'] === '917874949091'; // No +, no spaces
    });
});

it('handles phone number with various formats', function (string $phoneCode, string $phoneNumber, string $expected) {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $job = new SendWhatsAppCampaign(
        campaignName: 'test_campaign',
        phoneCode: $phoneCode,
        phoneNumber: $phoneNumber,
        templateParams: []
    );

    $job->handle();

    Http::assertSent(function ($request) use ($expected) {
        return $request['destination'] === $expected;
    });
})->with([
    ['+91', '78749 49091', '917874949091'],
    ['+91', '7874949091', '917874949091'],
    ['91', '78749 49091', '917874949091'],
    ['+1', '555 123 4567', '15551234567'],
]);

it('sends all template parameters', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $job = new SendWhatsAppCampaign(
        campaignName: 'booking_received',
        phoneCode: '+91',
        phoneNumber: '9876543210',
        templateParams: [
            'John Doe',
            'Auto Expo 2025',
            'A1, A2, B3',
            'ABC12345',
            '45',
            '67,500.00',
        ]
    );

    $job->handle();

    Http::assertSent(function ($request) {
        return $request['campaignName'] === 'booking_received'
            && $request['templateParams'] === [
                'John Doe',
                'Auto Expo 2025',
                'A1, A2, B3',
                'ABC12345',
                '45',
                '67,500.00',
            ];
    });
});
