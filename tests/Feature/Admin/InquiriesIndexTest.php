<?php

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Livewire;

test('admin can access inquiries index', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.index'))
        ->assertSuccessful()
        ->assertSee('Booking Inquiries');
});

test('non-admin cannot access inquiries index', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.inquiries.index'))
        ->assertForbidden();
});

test('inquiries index respects tab query parameter', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->withQueryParams(['tab' => 'payment_pending'])
        ->test('admin.inquiries.index')
        ->assertSet('statusFilter', 'payment_pending');
});

test('inquiries index filters by pending approval when tab is set', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();

    // Create bookings with different statuses
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'brand_name' => 'Pending Brand',
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'Completed Brand',
    ]);

    Livewire::actingAs($admin)
        ->withQueryParams(['tab' => 'pending_approval'])
        ->test('admin.inquiries.index')
        ->assertSet('statusFilter', 'pending_approval')
        ->assertSee('Pending Brand')
        ->assertDontSee('Completed Brand');
});

test('inquiries index filters by payment pending when tab is set', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'brand_name' => 'Payment Pending Brand',
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'Completed Brand',
    ]);

    Livewire::actingAs($admin)
        ->withQueryParams(['tab' => 'payment_pending'])
        ->test('admin.inquiries.index')
        ->assertSet('statusFilter', 'payment_pending')
        ->assertSee('Payment Pending Brand')
        ->assertDontSee('Completed Brand');
});

test('inquiries index filters by payment completed when tab is set', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'Completed Brand',
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'brand_name' => 'Pending Brand',
    ]);

    Livewire::actingAs($admin)
        ->withQueryParams(['tab' => 'payment_completed'])
        ->test('admin.inquiries.index')
        ->assertSet('statusFilter', 'payment_completed')
        ->assertSee('Completed Brand')
        ->assertDontSee('Pending Brand');
});

test('inquiries index defaults to all tab when no query parameter', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('admin.inquiries.index')
        ->assertSet('statusFilter', 'all');
});

test('inquiries index shows all bookings when tab is all', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'brand_name' => 'Pending Brand',
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'Completed Brand',
    ]);

    Livewire::actingAs($admin)
        ->withQueryParams(['tab' => 'all'])
        ->test('admin.inquiries.index')
        ->assertSet('statusFilter', 'all')
        ->assertSee('Pending Brand')
        ->assertSee('Completed Brand');
});

test('inquiries index displays part payment information', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();

    // Create booking with no payment
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'No Payment Brand',
    ]);

    // Create booking with partial payment (50%)
    $partialBooking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Partial Payment Brand',
    ]);
    // Record a partial payment
    $partialBooking->recordPayment($partialBooking->total_with_gst / 2, 'cash');

    // Create booking with full payment
    $fullBooking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Full Payment Brand',
    ]);
    // Record full payment
    $fullBooking->recordPayment($fullBooking->total_with_gst, 'cash');

    Livewire::actingAs($admin)
        ->test('admin.inquiries.index')
        ->assertSee('Part Payment')
        ->assertSee('Not paid')
        ->assertSee('50% paid');
});
