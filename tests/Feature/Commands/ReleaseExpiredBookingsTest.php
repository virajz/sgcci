<?php

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Exhibition;

test('command releases bookings that have expired payment deadline', function () {
    $exhibition = Exhibition::factory()->create();

    // Create a booking that expired 1 hour ago
    $expiredBooking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->subHour(),
        'selected_stalls' => ['35', '47'],
    ]);

    // Create a booking that expires in the future
    $activeBooking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addHours(24),
        'selected_stalls' => ['59', '71'],
    ]);

    $this->artisan('bookings:release-expired')
        ->expectsOutput('Checking for expired bookings...')
        ->expectsOutput('Found 1 expired booking(s).')
        ->expectsOutput("Released booking #{$expiredBooking->booking_code} (stalls: 35, 47)")
        ->expectsOutput('Successfully released 1 expired booking(s).')
        ->assertExitCode(0);

    $expiredBooking->refresh();
    $activeBooking->refresh();

    expect($expiredBooking->status)->toBe(BookingStatus::Expired)
        ->and($activeBooking->status)->toBe(BookingStatus::PaymentPending);
});

test('command handles multiple expired bookings', function () {
    $exhibition = Exhibition::factory()->create();

    // Create 3 expired bookings
    $expiredBookings = collect([
        Booking::factory()->create([
            'exhibition_id' => $exhibition->id,
            'status' => BookingStatus::PaymentPending,
            'payment_due_at' => now()->subHours(2),
        ]),
        Booking::factory()->create([
            'exhibition_id' => $exhibition->id,
            'status' => BookingStatus::PaymentPending,
            'payment_due_at' => now()->subDay(),
        ]),
        Booking::factory()->create([
            'exhibition_id' => $exhibition->id,
            'status' => BookingStatus::PaymentPending,
            'payment_due_at' => now()->subMinutes(30),
        ]),
    ]);

    $this->artisan('bookings:release-expired')
        ->expectsOutput('Found 3 expired booking(s).')
        ->assertExitCode(0);

    $expiredBookings->each(function ($booking) {
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::Expired);
    });
});

test('command does nothing when no bookings have expired', function () {
    $exhibition = Exhibition::factory()->create();

    // Create bookings that haven't expired yet
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addHours(48),
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addDay(),
    ]);

    $this->artisan('bookings:release-expired')
        ->expectsOutput('No expired bookings found.')
        ->assertExitCode(0);
});

test('command only releases payment pending bookings', function () {
    $exhibition = Exhibition::factory()->create();

    // Create bookings in various statuses with past due dates
    $pendingApproval = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'payment_due_at' => now()->subHours(2),
    ]);

    $allotted = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'payment_due_at' => now()->subHours(2),
    ]);

    $rejected = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Rejected,
        'payment_due_at' => now()->subHours(2),
    ]);

    // Only this one should be released
    $paymentPending = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->subHours(2),
    ]);

    $this->artisan('bookings:release-expired')
        ->expectsOutput('Found 1 expired booking(s).')
        ->assertExitCode(0);

    $pendingApproval->refresh();
    $allotted->refresh();
    $rejected->refresh();
    $paymentPending->refresh();

    expect($pendingApproval->status)->toBe(BookingStatus::PendingApproval)
        ->and($allotted->status)->toBe(BookingStatus::PaymentCompleted)
        ->and($rejected->status)->toBe(BookingStatus::Rejected)
        ->and($paymentPending->status)->toBe(BookingStatus::Expired);
});

test('command respects exact 72 hour deadline', function () {
    $exhibition = Exhibition::factory()->create();

    // Booking approved exactly 72 hours ago + 1 second (should expire)
    $expiredByOneSecond = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->subSeconds(1),
    ]);

    // Booking expires in exactly 1 second (should NOT expire)
    $expiresInOneSecond = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addSeconds(1),
    ]);

    $this->artisan('bookings:release-expired')
        ->expectsOutput('Found 1 expired booking(s).')
        ->assertExitCode(0);

    $expiredByOneSecond->refresh();
    $expiresInOneSecond->refresh();

    expect($expiredByOneSecond->status)->toBe(BookingStatus::Expired)
        ->and($expiresInOneSecond->status)->toBe(BookingStatus::PaymentPending);
});

test('hourly schedule ensures timely release of expired bookings', function () {
    // This test verifies that bookings are released properly
    // regardless of the time they were approved

    $exhibition = Exhibition::factory()->create();

    // Simulate a booking that was approved at 2:00 PM
    // payment_due_at would be 2:00 PM three days later
    $approvedAt2PM = now()->setTime(14, 0, 0);
    $paymentDueAt = $approvedAt2PM->copy()->addDays(3); // 2:00 PM, 3 days later

    // Create booking with past due date (30 minutes past deadline)
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'super_admin_approved_at' => $approvedAt2PM->copy()->subDays(3),
        'payment_due_at' => now()->subMinutes(30),
    ]);

    // With hourly schedule, the command will catch this within the next hour
    // which is much better than daily schedule that could be up to 24 hours late

    $this->artisan('bookings:release-expired')
        ->expectsOutput('Found 1 expired booking(s).')
        ->assertExitCode(0);

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Expired);
});
