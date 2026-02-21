<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use App\Models\Exhibition;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->exhibition = Exhibition::factory()->create();
    config(['services.whatsapp.enabled' => true]);
});

it('shows bookings in dry run without dispatching jobs', function () {
    Queue::fake();

    $booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('DRY RUN')
        ->expectsOutputToContain($booking->booking_code)
        ->expectsOutputToContain('Feb 28, 2026');

    Queue::assertNothingPushed();
});

it('only targets payment pending bookings with partial payments', function () {
    Queue::fake();

    // Should be included
    $partial = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
    ]);

    // Should be excluded — no payment made
    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 0,
        'remaining_amount' => 20000,
    ]);

    // Should be excluded — payment completed
    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 20000,
        'remaining_amount' => 0,
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('1 booking(s)');

    Queue::assertNothingPushed();
});

it('dispatches booking_confirmationpayment with correct params and Feb 28 deadline', function () {
    Queue::fake();

    $booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 50000,
        'remaining_amount' => 50000,
        'payment_link' => 'https://sgcci.test/payment/TESTCODE',
    ]);

    $this->artisan('bookings:send-partial-payment-reminders')
        ->expectsConfirmation('Send reminders to all 1 booking(s)?', 'yes')
        ->assertSuccessful();

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($booking) {
        return $job->campaignName === 'booking_confirmationpayment'
            && $job->phoneCode === $booking->phone_code
            && $job->phoneNumber === $booking->phone_number
            && $job->templateParams[3] === $booking->booking_code
            && $job->templateParams[6] === 'Feb 28, 2026'
            && $job->templateParams[7] === $booking->payment_link
            && $job->templateParams[8] === 'Feb 28, 2026';
    });
});

it('aborts without sending if user declines confirmation', function () {
    Queue::fake();

    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
    ]);

    $this->artisan('bookings:send-partial-payment-reminders')
        ->expectsConfirmation('Send reminders to all 1 booking(s)?', 'no')
        ->assertSuccessful()
        ->expectsOutputToContain('Aborted');

    Queue::assertNothingPushed();
});

it('reports failure when whatsapp is disabled', function () {
    Queue::fake();
    config(['services.whatsapp.enabled' => false]);

    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --force')
        ->assertFailed();

    Queue::assertNothingPushed();
});

it('outputs nothing to do when no partial payment bookings exist', function () {
    Queue::fake();

    $this->artisan('bookings:send-partial-payment-reminders')
        ->assertSuccessful()
        ->expectsOutputToContain('No bookings found');

    Queue::assertNothingPushed();
});

it('skips bookings reminded within the last 2 days', function () {
    Queue::fake();

    // Should be skipped — reminded 1 day ago
    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
        'last_partial_reminder_sent_at' => now()->subDay(),
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('No bookings found');

    Queue::assertNothingPushed();
});

it('includes bookings whose last reminder was more than 2 days ago', function () {
    Queue::fake();

    $booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
        'last_partial_reminder_sent_at' => now()->subDays(3),
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('1 booking(s)')
        ->expectsOutputToContain($booking->booking_code);
});

it('skips confirmation prompt when --force flag is used', function () {
    Queue::fake();

    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --force')
        ->assertSuccessful()
        ->expectsOutputToContain('Sent: 1');

    Queue::assertPushed(SendWhatsAppCampaign::class, 1);
});

it('updates last_partial_reminder_sent_at after successful send', function () {
    Queue::fake();

    $booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'amount_paid' => 10000,
        'remaining_amount' => 10000,
        'last_partial_reminder_sent_at' => null,
    ]);

    $this->artisan('bookings:send-partial-payment-reminders --force')
        ->assertSuccessful();

    expect($booking->fresh()->last_partial_reminder_sent_at)->not->toBeNull()
        ->and($booking->fresh()->last_partial_reminder_sent_at->isToday())->toBeTrue();
});
