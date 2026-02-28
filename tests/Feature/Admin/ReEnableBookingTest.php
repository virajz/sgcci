<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Livewire\Admin\Inquiries\Show;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Livewire;

test('admin can re-enable expired booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Expired,
        'payment_due_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking')
        ->assertDispatched('booking-updated');

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::PaymentPending)
        ->and($booking->payment_due_at)->toBeGreaterThan(now()->addDays(2))
        ->and($booking->payment_link_sent_at)->not->toBeNull();
});

test('admin can re-enable cancelled booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Cancelled,
        'rejected_by' => $admin->id,
        'rejected_at' => now()->subDays(2),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking')
        ->assertDispatched('booking-updated');

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::PaymentPending)
        ->and($booking->payment_due_at)->toBeGreaterThan(now()->addDays(2))
        ->and($booking->payment_link_sent_at)->not->toBeNull();
});

test('admin can re-enable rejected booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Rejected,
        'rejection_reason' => 'Test rejection reason',
        'rejected_by' => $admin->id,
        'rejected_at' => now()->subDays(1),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking')
        ->assertDispatched('booking-updated');

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::PaymentPending)
        ->and($booking->payment_due_at)->toBeGreaterThan(now()->addDays(2))
        ->and($booking->payment_link_sent_at)->not->toBeNull();
});

test('super admin can re-enable expired booking', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Expired,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking')
        ->assertDispatched('booking-updated');

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::PaymentPending);
});

test('admin cannot re-enable refunded booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Refunded,
        'refund_reason' => 'Customer requested',
        'refunded_by' => $admin->id,
        'refunded_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking');

    $booking->refresh();

    // Status should not have changed
    expect($booking->status)->toBe(BookingStatus::Refunded);
});

test('admin cannot re-enable payment completed booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'payment_completed_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking');

    $booking->refresh();

    // Status should not have changed
    expect($booking->status)->toBe(BookingStatus::PaymentCompleted);
});

test('admin cannot re-enable payment pending booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addDays(2),
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('reEnableExpiredBooking');

    $booking->refresh();

    // Status should not have changed
    expect($booking->status)->toBe(BookingStatus::PaymentPending);
});

test('non-admin cannot re-enable expired booking', function () {
    $user = User::factory()->create(['role' => 'user']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Expired,
    ]);

    $this->actingAs($user);

    $this->get(route('admin.inquiries.show', $booking))
        ->assertForbidden();
});

test('re-enable button visible for expired bookings', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Expired,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('Re-enable Booking');
});

test('re-enable button visible for cancelled bookings', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Cancelled,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('Re-enable Booking');
});

test('re-enable button visible for rejected bookings', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::Rejected,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.show', $booking))
        ->assertSuccessful()
        ->assertSee('Re-enable Booking');
});
