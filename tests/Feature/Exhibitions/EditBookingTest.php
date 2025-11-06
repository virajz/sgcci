<?php

declare(strict_types=1);

use App\Livewire\Exhibitions\EditBooking;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Livewire;

test('edit booking page loads successfully', function () {
    $this->get(route('exhibitions.booking.edit'))
        ->assertSuccessful()
        ->assertSeeLivewire(EditBooking::class);
});

test('user can find booking with valid code', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
    ]);

    $component = Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking');

    $component->assertHasNoErrors()
        ->assertSet('bookingFound', true);
});

test('user cannot find booking with invalid code', function () {
    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'INVALID1')
        ->call('findBooking')
        ->assertSet('bookingFound', false);
});

test('user can update booking details', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
        'brand_name' => 'Original Brand',
        'contact_person' => 'Original Person',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'original@example.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'space_type' => 'standard',
    ]);

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->set('brandName', 'Updated Brand')
        ->set('contactPerson', 'Updated Person')
        ->set('phoneNumber', '99999 88888')
        ->set('email', 'updated@example.com')
        ->set('city', 'Mumbai')
        ->set('productProfile', ['4-wheelers', '2-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors()
        ->assertSet('updateSuccessful', true)
        ->assertSee('Booking Updated Successfully!');

    $booking->refresh();

    expect($booking->brand_name)->toBe('Updated Brand')
        ->and($booking->contact_person)->toBe('Updated Person')
        ->and($booking->phone_number)->toBe('99999 88888')
        ->and($booking->email)->toBe('updated@example.com')
        ->and($booking->city)->toBe('Mumbai')
        ->and($booking->product_profile)->toBe(['4-wheelers', '2-wheelers']);
});

test('user cannot change selected stalls when editing', function () {
    $exhibition = Exhibition::factory()->create();
    $originalStalls = ['A1', 'A2', 'A3'];
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
        'selected_stalls' => $originalStalls,
        'brand_name' => 'Test Brand',
        'contact_person' => 'Test Person',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'test@example.com',
        'product_profile' => ['4-wheelers'],
    ]);

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->set('brandName', 'Updated Brand')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->selected_stalls)->toBe($originalStalls);
});

test('pricing is recalculated when updating booking', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
        'selected_stalls' => ['A1', 'A2'],
        'brand_name' => 'Test Brand',
        'contact_person' => 'Test Person',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'test@example.com',
        'has_exhibited_before' => false,
        'is_sgcci_member' => false,
        'space_type' => 'standard',
        'product_profile' => ['4-wheelers'],
    ]);

    $originalTotal = $booking->total_with_gst;

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->set('hasExhibitedBefore', true)
        ->set('participationYears', ['2019', '2024'])
        ->set('isSgcciMember', true)
        ->set('membershipType', 'gold-member')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    // Pricing should be different due to discounts
    expect($booking->total_with_gst)->not->toBe($originalTotal)
        ->and($booking->discount_percentage)->toBeGreaterThan(0);
});

test('validation works when updating booking', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
    ]);

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->set('brandName', '')
        ->set('email', 'invalid-email')
        ->set('productProfile', [])
        ->call('updateBooking')
        ->assertHasErrors(['brandName', 'email', 'productProfile']);
});

test('custom city is saved when Others is selected', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
        'city' => 'Surat',
        'brand_name' => 'Test Brand',
        'contact_person' => 'Test Person',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'test@example.com',
        'product_profile' => ['4-wheelers'],
    ]);

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->set('city', 'Others')
        ->set('customCity', 'New City Name')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->city)->toBe('New City Name');
});

test('user cannot edit booking with payment received', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
        'amount_paid' => 5000,
    ]);

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->assertSet('bookingFound', true)
        ->assertSet('paymentReceived', true)
        ->assertSee('Payment Received - Cannot Edit');
});

test('booking status resets to pending approval after edit', function () {
    $exhibition = Exhibition::factory()->create();
    $admin = User::factory()->admin()->create();

    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'booking_code' => 'TEST1234',
        'brand_name' => 'Test Brand',
        'contact_person' => 'Test Person',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'test@example.com',
        'product_profile' => ['4-wheelers'],
        'status' => \App\BookingStatus::ApprovedByAdmin,
        'admin_approved_by' => $admin->id,
        'admin_approved_at' => now(),
        'amount_paid' => 0,
    ]);

    Livewire::test(EditBooking::class)
        ->set('bookingCode', 'TEST1234')
        ->call('findBooking')
        ->set('brandName', 'Updated Brand')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(\App\BookingStatus::PendingApproval)
        ->and($booking->admin_approved_by)->toBeNull()
        ->and($booking->admin_approved_at)->toBeNull();
});
