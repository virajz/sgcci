<?php

use App\BookingStatus;
use App\Livewire\Admin\Inquiries\Show;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Livewire;

test('admin can verify booking and change status to approved by admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('approve')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::ApprovedByAdmin)
        ->and($booking->admin_approved_by)->toBe($admin->id)
        ->and($booking->admin_approved_at)->not->toBeNull();
});

test('super admin can approve and send payment link setting status to payment pending', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::ApprovedByAdmin,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('approve')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::PaymentPending)
        ->and($booking->super_admin_approved_by)->toBe($superAdmin->id)
        ->and($booking->super_admin_approved_at)->not->toBeNull()
        ->and($booking->payment_link)->not->toBeNull()
        ->and($booking->payment_link_sent_at)->not->toBeNull()
        ->and($booking->payment_due_at)->not->toBeNull();
});

test('super admin can mark payment as completed and allot stalls', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'super_admin_approved_by' => $superAdmin->id,
        'super_admin_approved_at' => now(),
        'payment_link' => 'http://example.com/payment/TEST123',
        'payment_link_sent_at' => now(),
        'payment_due_at' => now()->addDays(3),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('markPaymentCompleted')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Allotted)
        ->and($booking->payment_completed_at)->not->toBeNull();
});

test('only super admin can mark payment as completed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('markPaymentCompleted')
        ->assertHasNoErrors();

    $booking->refresh();

    // Status should remain PaymentPending as admin cannot mark it complete
    expect($booking->status)->toBe(BookingStatus::PaymentPending)
        ->and($booking->payment_completed_at)->toBeNull();
});

test('payment can only be marked completed if status is payment pending', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::ApprovedByAdmin,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('markPaymentCompleted')
        ->assertHasNoErrors();

    $booking->refresh();

    // Status should remain ApprovedByAdmin
    expect($booking->status)->toBe(BookingStatus::ApprovedByAdmin)
        ->and($booking->payment_completed_at)->toBeNull();
});

test('stalls are only counted as allotted after payment completion', function () {
    $exhibition = Exhibition::factory()->create();

    // Create a booking that's payment pending
    $paymentPendingBooking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'selected_stalls' => ['35', '47'],
    ]);

    // Create a booking that's allotted (payment completed)
    $allottedBooking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Allotted,
        'selected_stalls' => ['59', '71'],
        'payment_completed_at' => now(),
    ]);

    $stallSelector = new \App\Livewire\StallSelector;
    $stallSelector->exhibitionId = $exhibition->id;
    $bookedStalls = $stallSelector->getBookedStallsProperty();

    // Payment pending stalls should be 'reserved', not 'allotted'
    expect($bookedStalls['35'])->toBe('reserved')
        ->and($bookedStalls['47'])->toBe('reserved');

    // Allotted stalls (but status is Allotted not PaymentCompleted) should be 'reserved'
    expect($bookedStalls['59'])->toBe('reserved')
        ->and($bookedStalls['71'])->toBe('reserved');
});

test('complete booking approval workflow from pending to allotted', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    // Step 1: Admin verifies booking
    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('approve');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::ApprovedByAdmin);

    // Step 2: Super admin approves and sends payment link
    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('approve');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::PaymentPending);

    // Step 3: Super admin marks payment as completed
    Livewire::test(Show::class, ['booking' => $booking])
        ->call('markPaymentCompleted');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Allotted)
        ->and($booking->payment_completed_at)->not->toBeNull();
});
