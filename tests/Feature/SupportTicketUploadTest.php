<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('can upload file and returns path and original filename', function () {
    $file = UploadedFile::fake()->image('investor.jpeg');

    $response = $this->withoutMiddleware()->postJson('/support-tickets/upload', [
        'document' => $file,
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['path', 'filename', 'url']);
    expect($response->json('filename'))->toBe('investor.jpeg');
    expect($response->json('path'))->toContain('support-tickets/');
    expect($response->json('url'))->toBeString();

    Storage::disk('public')->assertExists($response->json('path'));
});

test('support ticket stores documents with original filename', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatus::Rejected,
    ]);

    $file = UploadedFile::fake()->image('test-image.png');

    $uploadResponse = $this->withoutMiddleware()->postJson('/support-tickets/upload', [
        'document' => $file,
    ]);

    $uploadResponse->assertSuccessful();

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'booking_code' => $booking->booking_code,
    ]);

    expect($uploadResponse->json())->toHaveKeys(['path', 'filename', 'url']);
    expect($uploadResponse->json('filename'))->toBe('test-image.png');
});

test('validates file upload requirements', function () {
    $response = $this->withoutMiddleware()->postJson('/support-tickets/upload', [
        'document' => UploadedFile::fake()->create('document.txt', 100),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('document');
});

test('rejects files larger than 5MB', function () {
    $response = $this->withoutMiddleware()->postJson('/support-tickets/upload', [
        'document' => UploadedFile::fake()->create('large.pdf', 6000),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('document');
});
