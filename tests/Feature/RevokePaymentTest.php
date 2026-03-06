<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Livewire\Admin\Inquiries\Show;
use App\Models\Booking;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('super admin can revoke a payment entry', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->create([
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 400000,
        'remaining_amount' => 0,
        'payment_history' => [
            ['amount' => 200000, 'method' => 'cc_avenue', 'transaction_id' => 'TXN001', 'recorded_at' => now()->toDateTimeString(), 'recorded_by' => $superAdmin->id],
            ['amount' => 200000, 'method' => 'cc_avenue', 'transaction_id' => 'TXN001', 'recorded_at' => now()->addMinute()->toDateTimeString(), 'recorded_by' => $superAdmin->id],
        ],
    ]);

    Livewire::actingAs($superAdmin)
        ->test(Show::class, ['booking' => $booking])
        ->call('openRevokePaymentModal', 1)
        ->assertSet('showRevokePaymentModal', true)
        ->assertSet('revokePaymentIndex', 1)
        ->call('revokePayment')
        ->assertSet('showRevokePaymentModal', false);

    $booking->refresh();

    expect($booking->payment_history)->toHaveCount(1)
        ->and((float) $booking->amount_paid)->toBe(200000.0)
        ->and((float) $booking->remaining_amount)->toBeGreaterThan(0)
        ->and($booking->status)->toBe(BookingStatus::PaymentPending);
});

test('revoking duplicate entry reduces amount paid and reverts payment completed status', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->create([
        'status' => BookingStatus::PaymentCompleted,
        'amount_paid' => 800000,
        'remaining_amount' => 0,
        'payment_completed_at' => now(),
        'payment_history' => [
            ['amount' => 400000, 'method' => 'cc_avenue', 'transaction_id' => 'TXN001', 'recorded_at' => now()->toDateTimeString(), 'recorded_by' => $superAdmin->id],
            ['amount' => 400000, 'method' => 'cc_avenue', 'transaction_id' => 'TXN001', 'recorded_at' => now()->addMinute()->toDateTimeString(), 'recorded_by' => $superAdmin->id],
        ],
    ]);

    Livewire::actingAs($superAdmin)
        ->test(Show::class, ['booking' => $booking])
        ->call('openRevokePaymentModal', 1)
        ->call('revokePayment');

    $booking->refresh();

    expect($booking->payment_history)->toHaveCount(1)
        ->and((float) $booking->amount_paid)->toBe(400000.0)
        ->and($booking->status)->toBe(BookingStatus::PaymentPending);
});

test('non-super-admin cannot revoke payment entries', function () {
    $admin = User::factory()->admin()->create();
    $booking = Booking::factory()->create([
        'payment_history' => [
            ['amount' => 100000, 'method' => 'manual', 'transaction_id' => null, 'recorded_at' => now()->toDateTimeString(), 'recorded_by' => $admin->id],
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(Show::class, ['booking' => $booking])
        ->call('openRevokePaymentModal', 0)
        ->assertSet('showRevokePaymentModal', false);

    $booking->refresh();

    expect($booking->payment_history)->toHaveCount(1);
});

test('revoking with invalid index does nothing', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->create([
        'payment_history' => [
            ['amount' => 100000, 'method' => 'manual', 'transaction_id' => null, 'recorded_at' => now()->toDateTimeString(), 'recorded_by' => $superAdmin->id],
        ],
    ]);

    Livewire::actingAs($superAdmin)
        ->test(Show::class, ['booking' => $booking])
        ->call('openRevokePaymentModal', 99)
        ->assertSet('showRevokePaymentModal', false);

    $booking->refresh();

    expect($booking->payment_history)->toHaveCount(1);
});
