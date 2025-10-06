<?php

declare(strict_types=1);

use App\Livewire\Exhibitions\ThankYou;
use App\Models\Booking;
use App\Models\Exhibition;

test('thank you page displays booking details', function () {
    $exhibition = Exhibition::factory()->create(['title' => 'Auto Expo 2025']);

    $booking = Booking::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers', '2-wheelers'],
        'has_exhibited_before' => true,
        'participation_years' => ['2019', '2024'],
        'is_sgcci_member' => true,
        'membership_type' => 'gold-member',
        'selected_stalls' => ['A1', 'A2'],
    ]);

    \Livewire\Livewire::test(ThankYou::class, ['exhibition' => $exhibition, 'bookingCode' => $booking->booking_code])
        ->assertSee('Booking Confirmed!')
        ->assertSee($booking->booking_code)
        ->assertSee('Test Motors')
        ->assertSee('John Doe')
        ->assertSee('john@testmotors.com')
        ->assertSee('Auto Expo 2025')
        ->assertSee('A1')
        ->assertSee('A2');
});

test('thank you page returns 404 for invalid booking code', function () {
    $exhibition = Exhibition::factory()->create();

    $this->get(route('exhibitions.booking.thank-you', ['exhibition' => $exhibition, 'bookingCode' => 'INVALID99']))
        ->assertNotFound();
});

test('thank you page shows all selected stalls', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['A1', 'A2', 'B1', 'B2'],
    ]);

    \Livewire\Livewire::test(ThankYou::class, ['exhibition' => $exhibition, 'bookingCode' => $booking->booking_code])
        ->assertSee('A1')
        ->assertSee('A2')
        ->assertSee('B1')
        ->assertSee('B2')
        ->assertSee('Selected Stalls');
});

test('thank you page shows product profiles', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers', '2-wheelers', 'automobile-ancillaries'],
        'selected_stalls' => ['A1'],
    ]);

    \Livewire\Livewire::test(ThankYou::class, ['exhibition' => $exhibition, 'bookingCode' => $booking->booking_code])
        ->assertSee('4 Wheelers')
        ->assertSee('2 Wheelers')
        ->assertSee('Automobile Ancillaries');
});

test('thank you page displays pricing information', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['35'], // 7x22 = 154 sq m
    ]);

    \Livewire\Livewire::test(ThankYou::class, ['exhibition' => $exhibition, 'bookingCode' => $booking->booking_code])
        ->assertSee('Pricing Details')
        ->assertSee('154.00 sq m')
        ->assertSee('₹750.00')
        ->assertSee('₹115,500.00');
});

test('thank you page displays GST number when provided', function () {
    $exhibition = Exhibition::factory()->create();

    $booking = Booking::create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Test Motors',
        'contact_person' => 'John Doe',
        'phone_code' => '+91',
        'phone_number' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'gst_number' => '22AAAAA0000A1Z5',
        'product_profile' => ['4-wheelers'],
        'selected_stalls' => ['A1'],
    ]);

    \Livewire\Livewire::test(ThankYou::class, ['exhibition' => $exhibition, 'bookingCode' => $booking->booking_code])
        ->assertSee('GST Number')
        ->assertSee('22AAAAA0000A1Z5');
});
