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
        ->assertRedirect();

    $this->assertDatabaseHas('bookings', [
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'email' => 'john@testmotors.com',
    ]);

    expect(BookingModel::count())->toBe(1);

    $booking = BookingModel::first();
    expect($booking->product_profile)->toBeArray()
        ->and($booking->selected_stalls)->toBeArray()
        ->and($booking->participation_years)->toBeArray()
        ->and($booking->booking_code)->toBeString()
        ->and(strlen($booking->booking_code))->toBe(8);
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
        ->assertRedirect();

    expect(BookingModel::count())->toBe(1);
});

test('booking generates unique 8 character alphanumeric code', function () {
    $exhibition = Exhibition::factory()->create();

    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['A1'])
        ->call('save');

    $booking = BookingModel::first();

    expect($booking->booking_code)
        ->toBeString()
        ->toHaveLength(8)
        ->toMatch('/^[A-Z0-9]{8}$/');
});

test('booking code is unique', function () {
    $exhibition = Exhibition::factory()->create();

    // Create first booking
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors 1')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['A1'])
        ->call('save');

    // Create second booking
    \Livewire\Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors 2')
        ->set('contactPerson', 'Jane Doe')
        ->set('phoneNumber', '98765 43211')
        ->set('email', 'jane@testmotors.com')
        ->set('productProfile', ['4-wheelers'])
        ->set('selectedStalls', ['A2'])
        ->call('save');

    $bookings = BookingModel::all();

    expect($bookings->count())->toBe(2)
        ->and($bookings->pluck('booking_code')->unique()->count())->toBe(2);
});
