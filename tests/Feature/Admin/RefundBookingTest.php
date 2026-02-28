<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Livewire\Admin\Inquiries\Show;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Livewire;

test('super admin can refund booking with payment', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 50000,
        'payment_completed_at' => now(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('openRefundModal')
        ->assertSet('showRefundModal', true)
        ->set('refundReason', 'Customer requested refund due to event cancellation')
        ->call('refundAndRelease')
        ->assertHasNoErrors()
        ->assertSet('showRefundModal', false);

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Refunded)
        ->and($booking->refund_reason)->toBe('Customer requested refund due to event cancellation')
        ->and($booking->refunded_by)->toBe($superAdmin->id)
        ->and($booking->refunded_at)->not->toBeNull();
});

test('admin can release stalls for any status', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('openReleaseModal')
        ->assertSet('showReleaseModal', true)
        ->set('releaseReason', 'Customer requested cancellation')
        ->call('releaseStalls')
        ->assertHasNoErrors()
        ->assertSet('showReleaseModal', false);

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->rejection_reason)->toBe('Customer requested cancellation')
        ->and($booking->rejected_by)->toBe($admin->id)
        ->and($booking->rejected_at)->not->toBeNull();
});

test('admin cannot refund booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 50000,
        'payment_completed_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('openRefundModal')
        ->set('refundReason', 'Customer requested refund')
        ->call('refundAndRelease')
        ->assertSet('showRefundModal', false);

    $booking->refresh();

    // Status should not have changed
    expect($booking->status)->toBe(BookingStatus::PaymentCompleted)
        ->and($booking->refunded_by)->toBeNull()
        ->and($booking->refunded_at)->toBeNull();
});

test('refund requires reason with minimum length', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 50000,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('refundReason', 'Short')
        ->call('refundAndRelease')
        ->assertHasErrors(['refundReason' => 'min']);
});

test('release requires reason with minimum length', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('releaseReason', 'Short')
        ->call('releaseStalls')
        ->assertHasErrors(['releaseReason' => 'min']);
});

test('refund requires reason field', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 50000,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('refundReason', '')
        ->call('refundAndRelease')
        ->assertHasErrors(['refundReason' => 'required']);
});

test('cannot refund booking without payment', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'amount_paid' => 0,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('refundReason', 'Customer requested refund')
        ->call('refundAndRelease')
        ->assertSet('showRefundModal', false);

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::PendingApproval)
        ->and($booking->refunded_by)->toBeNull()
        ->and($booking->refunded_at)->toBeNull();
});

test('refund button visible for super admin on paid bookings', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 50000,
        'payment_completed_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('openRefundModal', false);
});

test('release button visible for admin on bookings', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('openReleaseModal', false);
});

test('refund button not visible for regular admin', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 50000,
        'payment_completed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertDontSee('openRefundModal', false);
});

test('refunded booking shows in history', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Refunded,
        'amount_paid' => 50000,
        'refund_reason' => 'Event cancelled by organizer',
        'refunded_by' => $superAdmin->id,
        'refunded_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('Refunded')
        ->assertSee('Stalls Released')
        ->assertSee('Event cancelled by organizer')
        ->assertSee($superAdmin->name);
});

test('refund modal shows payment amount', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $amountPaid = 75000.50;
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => $amountPaid,
        'payment_completed_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('Amount to Refund: ₹'.number_format($amountPaid, 2));
});

test('refunded booking displays status badge', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Refunded,
        'amount_paid' => 50000,
        'refunded_by' => $superAdmin->id,
        'refunded_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('Refunded');
});
