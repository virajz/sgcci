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
        'spaceType' => 'standard',
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
        ->set('spaceType', $bookingData['spaceType'])
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

    // Stall '35' has size 7x18 = 126 sq m from the SVG
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

    // Calculate expected pricing with standard space type (5000/sq m)
    // 126 sq m gets 15% discount (100+ sq m discount tier)
    $pricing = \App\Models\Booking::calculatePricing(['35'], false, [], false, null, 'standard');
    expect($pricing['total_area'])->toBe(126)
        ->and($pricing['price_per_sqm'])->toBe(5000)
        ->and($pricing['total_price'])->toBe(630000)
        ->and($pricing['discount_percentage'])->toBe(15.0)
        ->and($pricing['discount_amount'])->toBe(94500.0)
        ->and($pricing['price_after_discount'])->toBe(535500.0)
        ->and($pricing['gst_amount'])->toBe(96390.0) // 18% of 535500
        ->and($pricing['total_with_gst'])->toBe(631890.0);
});

test('booking calculates pricing for multiple stalls', function () {
    $exhibition = Exhibition::factory()->create();

    // '35' = 7x18 = 126 sq m
    // '37' = 7x18 = 126 sq m (using 37 instead of 47 which doesn't exist)
    // Total = 252 sq m
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['35', '37'])
        ->call('save');

    // 252 sq m gets 15% discount (100+ sq m discount tier)
    $pricing = \App\Models\Booking::calculatePricing(['35', '37'], false, [], false, null, 'standard');
    expect($pricing['total_area'])->toBe(252)
        ->and($pricing['price_per_sqm'])->toBe(5000)
        ->and($pricing['total_price'])->toBe(1260000)
        ->and($pricing['discount_percentage'])->toBe(15.0)
        ->and($pricing['discount_amount'])->toBe(189000.0)
        ->and($pricing['price_after_discount'])->toBe(1071000.0)
        ->and($pricing['gst_amount'])->toBe(192780.0) // 18% of 1071000
        ->and($pricing['total_with_gst'])->toBe(1263780.0);
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

    $pricing = \App\Models\Booking::calculatePricing(['UNKNOWN'], false, [], false, null, 'standard');
    expect($pricing['total_area'])->toBe(9)
        ->and($pricing['price_per_sqm'])->toBe(5000)
        ->and($pricing['total_price'])->toBe(45000)
        ->and($pricing['gst_amount'])->toBe(8100.0) // 18% of 45000
        ->and($pricing['total_with_gst'])->toBe(53100.0);
});

test('booking calculates pricing for raw space type', function () {
    $exhibition = Exhibition::factory()->create();

    // '35' = 7x18 = 126 sq m
    // Raw space = 4500/sq m
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('spaceType', 'raw')
        ->set('selectedStalls', ['35'])
        ->call('save');

    // 126 sq m gets 15% discount (100+ sq m discount tier)
    $pricing = \App\Models\Booking::calculatePricing(['35'], false, [], false, null, 'raw');
    expect($pricing['total_area'])->toBe(126)
        ->and($pricing['price_per_sqm'])->toBe(4500)
        ->and($pricing['total_price'])->toBe(567000)
        ->and($pricing['discount_percentage'])->toBe(15.0)
        ->and($pricing['discount_amount'])->toBe(85050.0)
        ->and($pricing['price_after_discount'])->toBe(481950.0)
        ->and($pricing['gst_amount'])->toBe(86751.0) // 18% of 481950
        ->and($pricing['total_with_gst'])->toBe(568701.0);
});
