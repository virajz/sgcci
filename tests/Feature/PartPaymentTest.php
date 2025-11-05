<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => 'super-admin']);
    $this->exhibition = Exhibition::factory()->create();
});

test('booking model initializes part payment fields correctly', function () {
    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
    ]);
    $booking->save();

    expect((float) $booking->amount_paid)->toBe(0.00)
        ->and((float) $booking->remaining_amount)->toBe((float) $booking->total_with_gst)
        ->and($booking->partial_payment_deadline)->toBeNull()
        ->and($booking->payment_history)->toBeNull();
});

test('booking can record partial payment', function () {
    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
        'status' => BookingStatus::PaymentPending,
    ]);
    $booking->save();

    $totalAmount = $booking->total_with_gst;
    $halfAmount = round($totalAmount / 2, 2);

    $this->actingAs($this->superAdmin);

    $booking->recordPayment(
        amount: $halfAmount,
        method: 'cash',
        transactionId: 'TXN123'
    );

    $booking->refresh();

    expect((float) $booking->amount_paid)->toBe($halfAmount)
        ->and((float) $booking->remaining_amount)->toBe($totalAmount - $halfAmount)
        ->and($booking->hasPartialPayment())->toBeTrue()
        ->and($booking->payment_history)->toHaveCount(1)
        ->and((float) $booking->payment_history[0]['amount'])->toBe($halfAmount)
        ->and($booking->payment_history[0]['method'])->toBe('cash')
        ->and($booking->payment_history[0]['transaction_id'])->toBe('TXN123');
});

test('booking can record full payment', function () {
    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
        'status' => BookingStatus::PaymentPending,
    ]);
    $booking->save();

    $totalAmount = (float) $booking->total_with_gst;
    $halfAmount = round($totalAmount / 2, 2);

    // Record first payment
    $booking->update([
        'amount_paid' => $halfAmount,
        'remaining_amount' => $totalAmount - $halfAmount,
    ]);

    $this->actingAs($this->superAdmin);

    // Record second payment
    $booking->recordPayment(
        amount: $totalAmount - $halfAmount,
        method: 'bank_transfer',
        transactionId: 'TXN456'
    );

    $booking->refresh();

    expect((float) $booking->amount_paid)->toBe($totalAmount)
        ->and((float) $booking->remaining_amount)->toBe(0.00)
        ->and($booking->isPaymentCompleted())->toBeTrue()
        ->and($booking->hasPartialPayment())->toBeFalse()
        ->and($booking->getPaymentPercentage())->toBe(100.0)
        ->and($booking->status)->toBe(BookingStatus::Allotted)
        ->and($booking->payment_completed_at)->not->toBeNull()
        ->and($booking->payment_method)->toBe('bank_transfer')
        ->and($booking->payment_transaction_id)->toBe('TXN456');
});

test('payment percentage is calculated correctly', function () {
    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1', 'A2'],
    ]);
    $booking->save();

    // Update payment amounts manually after creation
    $booking->update([
        'amount_paid' => $booking->total_with_gst * 0.25,
        'remaining_amount' => $booking->total_with_gst * 0.75,
    ]);

    expect($booking->getPaymentPercentage())->toBe(25.0);

    $booking->update([
        'amount_paid' => $booking->total_with_gst * 0.75,
        'remaining_amount' => $booking->total_with_gst * 0.25,
    ]);
    expect($booking->getPaymentPercentage())->toBe(75.0);
});

test('payment deadline approaching detection works correctly', function () {
    // Deadline in 2 days - should be approaching
    $booking1 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
    ]);
    $booking1->save();
    $booking1->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
    ]);

    expect($booking1->isPaymentDeadlineApproaching())->toBeTrue();

    // Deadline in 5 days - should not be approaching
    $booking2 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A2'],
    ]);
    $booking2->save();
    $booking2->update([
        'partial_payment_deadline' => now()->addDays(5),
        'remaining_amount' => 5000.00,
    ]);

    expect($booking2->isPaymentDeadlineApproaching())->toBeFalse();

    // Deadline passed - should not be approaching
    $booking3 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A3'],
    ]);
    $booking3->save();
    $booking3->update([
        'partial_payment_deadline' => now()->subDay(),
        'remaining_amount' => 5000.00,
    ]);

    expect($booking3->isPaymentDeadlineApproaching())->toBeFalse();

    // Payment completed - should not be approaching
    $booking4 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A4'],
    ]);
    $booking4->save();
    $booking4->update([
        'partial_payment_deadline' => now()->addDays(2),
        'amount_paid' => $booking4->total_with_gst,
        'remaining_amount' => 0.00,
    ]);

    expect($booking4->isPaymentDeadlineApproaching())->toBeFalse();
});

test('payment overdue detection works correctly', function () {
    // Deadline passed
    $booking1 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
    ]);
    $booking1->save();
    $booking1->update([
        'partial_payment_deadline' => now()->subDay(),
        'remaining_amount' => 5000.00,
    ]);

    expect($booking1->isPaymentOverdue())->toBeTrue();

    // Deadline in future
    $booking2 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A2'],
    ]);
    $booking2->save();
    $booking2->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
    ]);

    expect($booking2->isPaymentOverdue())->toBeFalse();

    // Payment completed
    $booking3 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A3'],
    ]);
    $booking3->save();
    $booking3->update([
        'partial_payment_deadline' => now()->subDay(),
        'amount_paid' => $booking3->total_with_gst,
        'remaining_amount' => 0.00,
    ]);

    expect($booking3->isPaymentOverdue())->toBeFalse();
});

test('payment deadline approaching scope returns correct bookings', function () {
    // Clear any existing bookings
    Booking::query()->delete();

    // Should be included
    $booking1 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
    ]);
    $booking1->save();
    $booking1->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
    ]);

    // Should not be included - deadline too far
    $booking2 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A2'],
    ]);
    $booking2->save();
    $booking2->update([
        'partial_payment_deadline' => now()->addDays(5),
        'remaining_amount' => 5000.00,
    ]);

    // Should not be included - already paid
    $booking3 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A3'],
    ]);
    $booking3->save();
    $booking3->update([
        'partial_payment_deadline' => now()->addDays(2),
        'amount_paid' => $booking3->total_with_gst,
        'remaining_amount' => 0.00,
    ]);

    // Should not be included - deadline passed
    $booking4 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A4'],
    ]);
    $booking4->save();
    $booking4->update([
        'partial_payment_deadline' => now()->subDay(),
        'remaining_amount' => 5000.00,
    ]);

    $approaching = Booking::paymentDeadlineApproaching()->get();

    expect($approaching)->toHaveCount(1);
});

test('payment overdue scope returns correct bookings', function () {
    // Clear any existing bookings
    Booking::query()->delete();

    // Should be included
    $booking1 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
    ]);
    $booking1->save();
    $booking1->update([
        'partial_payment_deadline' => now()->subDay(),
        'remaining_amount' => 5000.00,
    ]);

    // Should not be included - deadline in future
    $booking2 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A2'],
    ]);
    $booking2->save();
    $booking2->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
    ]);

    // Should not be included - already paid
    $booking3 = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A3'],
    ]);
    $booking3->save();
    $booking3->update([
        'partial_payment_deadline' => now()->subDay(),
        'amount_paid' => $booking3->total_with_gst,
        'remaining_amount' => 0.00,
    ]);

    $overdue = Booking::paymentOverdue()->get();

    expect($overdue)->toHaveCount(1);
});

test('send payment reminders command processes bookings correctly', function () {
    Queue::fake();
    config(['services.whatsapp.enabled' => true]);

    // Create booking with approaching deadline
    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
        'phone_code' => '+91',
        'phone_number' => '9876543210',
    ]);
    $booking->save();
    $booking->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
    ]);

    $this->artisan('bookings:send-payment-reminders')
        ->assertSuccessful();

    Queue::assertPushed(SendWhatsAppCampaign::class);

    $booking->refresh();
    expect($booking->last_payment_reminder_sent_at)->not->toBeNull();
});

test('send payment reminders command does not send duplicate reminders within 24 hours', function () {
    Queue::fake();

    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
        'phone_code' => '+91',
        'phone_number' => '9876543210',
    ]);
    $booking->save();
    $booking->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
        'last_payment_reminder_sent_at' => now()->subHours(12),
    ]);

    $this->artisan('bookings:send-payment-reminders')
        ->assertSuccessful();

    Queue::assertNotPushed(SendWhatsAppCampaign::class);
});

test('send payment reminders command sends reminder if last reminder was more than 24 hours ago', function () {
    Queue::fake();
    config(['services.whatsapp.enabled' => true]);

    $booking = Booking::factory()->make([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['A1'],
        'phone_code' => '+91',
        'phone_number' => '9876543210',
    ]);
    $booking->save();
    $booking->update([
        'partial_payment_deadline' => now()->addDays(2),
        'remaining_amount' => 5000.00,
        'last_payment_reminder_sent_at' => now()->subDays(2),
    ]);

    $this->artisan('bookings:send-payment-reminders')
        ->assertSuccessful();

    Queue::assertPushed(SendWhatsAppCampaign::class);
});
