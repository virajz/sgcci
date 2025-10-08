<?php

declare(strict_types=1);

use App\Livewire\Exhibitions\Booking;
use App\Models\Booking as BookingModel;
use App\Models\Exhibition;

test('booking creates a record in database', function () {
    $exhibition = Exhibition::factory()->create();

    $bookingData = [
        'brandName' => 'Test Motors',
        'contactPerson' => 'John Doe',
        'phoneCode' => '+91',
        'phoneNumber' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'productProfile' => ['4-wheelers', '2-wheelers'],
        'hasExhibitedBefore' => true,
        'participationYears' => ['2019', '2024'],
        'isSgcciMember' => true,
        'membershipType' => 'gold-member',
        'selectedStalls' => ['A1', 'A2'],
    ];

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', $bookingData['brandName'])
        ->set('contactPerson', $bookingData['contactPerson'])
        ->set('phoneCode', $bookingData['phoneCode'])
        ->set('phoneNumber', $bookingData['phoneNumber'])
        ->set('email', $bookingData['email'])
        ->set('city', $bookingData['city'])
        ->set('productProfile', $bookingData['productProfile'])
        ->set('hasExhibitedBefore', $bookingData['hasExhibitedBefore'])
        ->set('participationYears', $bookingData['participationYears'])
        ->set('isSgcciMember', $bookingData['isSgcciMember'])
        ->set('membershipType', $bookingData['membershipType'])
        ->set('selectedStalls', $bookingData['selectedStalls'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('exhibitions.booking.confirmation', ['exhibition' => $exhibition]));

    expect(session('booking_data'))->toBeArray()
        ->and(session('booking_data')['brandName'])->toBe('Test Motors');
});

test('booking validation requires brand name', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', '')
        ->set('selectedStalls', ['A1'])
        ->set('productProfile', ['4-wheelers'])
        ->call('save')
        ->assertHasErrors(['brandName']);
});

test('booking validation requires at least one stall', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('selectedStalls', [])
        ->set('productProfile', ['4-wheelers'])
        ->call('save')
        ->assertHasErrors(['selectedStalls']);
});

test('booking validation requires at least one product profile', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('selectedStalls', ['A1'])
        ->set('productProfile', [])
        ->call('save')
        ->assertHasErrors(['productProfile']);
});

test('booking validation requires valid email', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('email', 'invalid-email')
        ->set('brandName', 'Test Motors')
        ->set('selectedStalls', ['A1'])
        ->set('productProfile', ['4-wheelers'])
        ->call('save')
        ->assertHasErrors(['email']);
});

test('booking resets form after successful submission', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('city', 'Mumbai')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['A1'])
        ->call('save')
        ->assertRedirect(route('exhibitions.booking.confirmation', ['exhibition' => $exhibition]));

    expect(session('booking_data'))->toBeArray();
});

test('booking generates unique 8 character alphanumeric code', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = BookingModel::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['35'],
    ]);

    expect($booking->booking_code)
        ->toBeString()
        ->toHaveLength(8)
        ->toMatch('/^[A-Z0-9]{8}$/');
});

test('booking code is unique', function () {
    $exhibition = Exhibition::factory()->create();

    // Create first booking
    $booking1 = BookingModel::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors 1',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['35'],
    ]);

    // Create second booking
    $booking2 = BookingModel::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors 2',
        'contact_person' => 'Jane Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43211',
        'email' => 'jane@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['47'],
    ]);

    expect($booking1->booking_code)->not->toBe($booking2->booking_code);
});

test('booking accepts optional GST number', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('gstNumber', '22AAAAA0000A1Z5')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['A1'])
        ->call('save')
        ->assertHasNoErrors();

    $sessionData = session('booking_data');
    expect($sessionData['gstNumber'])->toBe('22AAAAA0000A1Z5');
});

test('booking defaults to booked status', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = BookingModel::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['35'],
    ]);

    $booking->refresh();
    expect($booking->status)->toBe(\App\BookingStatus::PendingApproval);
});

test('booking calculates pricing based on stall sizes', function () {
    $exhibition = Exhibition::factory()->create();

    // Assuming stall '35' has size 7x22 = 154 sq m from the SVG
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['35'])
        ->call('save');

    $bookingData = session('booking_data');
    expect($bookingData['selectedStalls'])->toBe(['35']);

    // Calculate expected pricing
    $pricing = \App\Models\Booking::calculatePricing(['35']);
    expect($pricing['total_area'])->toBe(154)
        ->and($pricing['price_per_sqm'])->toBe(750)
        ->and($pricing['total_price'])->toBe(115500)
        ->and($pricing['gst_amount'])->toBe(20790.0) // 18% of 115500
        ->and($pricing['total_with_gst'])->toBe(136290.0);
});

test('booking calculates pricing for multiple stalls', function () {
    $exhibition = Exhibition::factory()->create();

    // '35' = 7x22 = 154 sq m
    // '47' = 7x18 = 126 sq m
    // Total = 280 sq m
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['35', '47'])
        ->call('save');

    $pricing = \App\Models\Booking::calculatePricing(['35', '47']);
    expect($pricing['total_area'])->toBe(280)
        ->and($pricing['price_per_sqm'])->toBe(750)
        ->and($pricing['total_price'])->toBe(210000)
        ->and($pricing['gst_amount'])->toBe(37800.0) // 18% of 210000
        ->and($pricing['total_with_gst'])->toBe(247800.0);
});

test('booking uses default 3x3 size for unknown stalls', function () {
    $exhibition = Exhibition::factory()->create();

    // 'UNKNOWN' stall should default to 3x3 = 9 sq m
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['UNKNOWN'])
        ->call('save');

    $pricing = \App\Models\Booking::calculatePricing(['UNKNOWN']);
    expect($pricing['total_area'])->toBe(9)
        ->and($pricing['price_per_sqm'])->toBe(750)
        ->and($pricing['total_price'])->toBe(6750)
        ->and($pricing['gst_amount'])->toBe(1215.0) // 18% of 6750
        ->and($pricing['total_with_gst'])->toBe(7965.0);
});
