<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\WhatsAppInquiry;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function whatsAppPayload(string $text, string $phone = '917874949091', string $userName = 'Viraj Zaveri'): array
{
    return [
        'data' => [
            'message' => [
                'phone_number' => $phone,
                'userName' => $userName,
                'message_content' => ['text' => $text],
                'message_type' => 'TEXT',
                'sender' => 'USER',
            ],
        ],
    ];
}

it('stores the raw webhook payload', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $this->postJson(route('webhook.whatsapp'), whatsAppPayload('Hello'))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect(WhatsAppWebhookLog::count())->toBe(1);
});

it('sends a text profile message when the booking code suffix matches', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Shreenath Kia',
        'profile_message_type' => 'text',
        'profile_message_text' => 'Thank you for your interest!',
    ]);

    $codeSuffix = substr($booking->booking_code, -6);

    $this->postJson(route('webhook.whatsapp'), whatsAppPayload("I want to know more about Shreenath Kia - {$codeSuffix}"))
        ->assertOk();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/messages')
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Thank you for your interest!';
    });
});

it('sends an image profile message', function () {
    Storage::fake();
    Storage::put('profile-messages/test.jpg', 'fake-image-content');

    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'profile_message_type' => 'image',
        'profile_message_text' => 'Check our products',
        'profile_message_media' => 'profile-messages/test.jpg',
    ]);

    $codeSuffix = substr($booking->booking_code, -6);

    $this->postJson(route('webhook.whatsapp'), whatsAppPayload("I want to know more about Brand - {$codeSuffix}"))
        ->assertOk();

    Http::assertSent(function ($request) {
        return $request['type'] === 'image'
            && $request['image']['caption'] === 'Check our products';
    });
});

it('records a WhatsApp inquiry for the sender', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'profile_message_type' => 'text',
        'profile_message_text' => 'Hello!',
    ]);

    $codeSuffix = substr($booking->booking_code, -6);

    $this->postJson(route('webhook.whatsapp'), whatsAppPayload(
        "I want to know more about Brand - {$codeSuffix}",
        '917874949091',
        'Viraj Zaveri',
    ))->assertOk();

    $inquiry = WhatsAppInquiry::where('booking_id', $booking->id)
        ->where('phone_number', '917874949091')
        ->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->name)->toBe('Viraj Zaveri');
});

it('does not create a duplicate inquiry for the same phone number', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'profile_message_type' => 'text',
        'profile_message_text' => 'Hello!',
    ]);

    $codeSuffix = substr($booking->booking_code, -6);
    $payload = whatsAppPayload("I want to know more about Brand - {$codeSuffix}");

    $this->postJson(route('webhook.whatsapp'), $payload)->assertOk();
    $this->postJson(route('webhook.whatsapp'), $payload)->assertOk();

    expect(WhatsAppInquiry::where('booking_id', $booking->id)->count())->toBe(1);
});

it('ignores messages that do not match the expected format', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $this->postJson(route('webhook.whatsapp'), whatsAppPayload('Hi there!'))
        ->assertOk();

    Http::assertNothingSent();
    expect(WhatsAppInquiry::count())->toBe(0);
});

it('ignores messages when no matching booking is found', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $this->postJson(route('webhook.whatsapp'), whatsAppPayload('I want to know more about Brand - XXXXXX'))
        ->assertOk();

    Http::assertNothingSent();
    expect(WhatsAppInquiry::count())->toBe(0);
});

it('ignores non-USER sender messages', function () {
    Http::fake(['*' => Http::response(['messages' => [['id' => 'msg-1']]], 200)]);

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create(['exhibition_id' => $exhibition->id]);
    $codeSuffix = substr($booking->booking_code, -6);

    $payload = [
        'data' => [
            'message' => [
                'phone_number' => '917874949091',
                'message_content' => ['text' => "I want to know more about Brand - {$codeSuffix}"],
                'message_type' => 'TEXT',
                'sender' => 'BUSINESS',
            ],
        ],
    ];

    $this->postJson(route('webhook.whatsapp'), $payload)->assertOk();

    Http::assertNothingSent();
});

it('handles payload with no data gracefully', function () {
    $this->postJson(route('webhook.whatsapp'), [])->assertOk();

    expect(WhatsAppWebhookLog::count())->toBe(1);
});
