<?php

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;

test('command skips bookings with admin@sgcci.com email', function () {
    $exhibition = Exhibition::factory()->create();

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'email' => 'admin@sgcci.com',
        'exhibitor_user_id' => null,
    ]);

    $this->artisan('bookings:generate-exhibitor-passwords')
        ->expectsOutput('No eligible bookings remaining after filtering.')
        ->assertExitCode(0);

    expect(User::where('email', 'admin@sgcci.com')->exists())->toBeFalse();
});

test('command warns and skips bookings with duplicate emails', function () {
    $exhibition = Exhibition::factory()->create();

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'email' => 'duplicate@example.com',
        'exhibitor_user_id' => null,
    ]);

    $booking2 = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'email' => 'duplicate@example.com',
        'exhibitor_user_id' => null,
    ]);

    $this->artisan('bookings:generate-exhibitor-passwords')
        ->expectsOutput('No eligible bookings remaining after filtering.')
        ->assertExitCode(0);

    $booking2->refresh();
    expect($booking2->exhibitor_user_id)->toBeNull();
});

test('command skips manual block bookings', function () {
    $exhibition = Exhibition::factory()->create();

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => true,
        'exhibitor_user_id' => null,
    ]);

    $this->artisan('bookings:generate-exhibitor-passwords')
        ->expectsOutput('No bookings found that need exhibitor accounts.')
        ->assertExitCode(0);
});

test('command creates exhibitor accounts when confirmed', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'email' => 'exhibitor@example.com',
        'exhibitor_user_id' => null,
    ]);

    $this->artisan('bookings:generate-exhibitor-passwords')
        ->expectsConfirmation('Create exhibitor accounts for these 1 booking(s)?', 'yes')
        ->assertExitCode(0);

    $booking->refresh();
    expect($booking->exhibitor_user_id)->not->toBeNull()
        ->and($booking->login_password)->not->toBeNull();

    $user = User::find($booking->exhibitor_user_id);
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('exhibitor')
        ->and($user->email)->toBe('exhibitor@example.com');
});

test('command cancels when confirmation is declined', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => null,
    ]);

    $this->artisan('bookings:generate-exhibitor-passwords')
        ->expectsConfirmation('Create exhibitor accounts for these 1 booking(s)?', 'no')
        ->expectsOutput('Operation cancelled.')
        ->assertExitCode(1);

    $booking->refresh();
    expect($booking->exhibitor_user_id)->toBeNull();
});

test('dry run previews without creating accounts', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => null,
    ]);

    $this->artisan('bookings:generate-exhibitor-passwords --dry-run')
        ->expectsOutputToContain('DRY RUN')
        ->assertExitCode(0);

    $booking->refresh();
    expect($booking->exhibitor_user_id)->toBeNull();
});
