<?php

use App\Livewire\Exhibitions\Confirmation;
use App\Models\Exhibition;
use Livewire\Livewire;

it('redirects to booking form when no session data', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Confirmation::class, ['exhibition' => $exhibition])
        ->assertRedirect(route('exhibitions.booking.show', $exhibition));
});

it('renders successfully with session data', function () {
    $exhibition = Exhibition::factory()->create();

    session()->put('booking_data', [
        'exhibitionId' => $exhibition->id,
        'brandName' => 'Test Motors',
        'contactPerson' => 'John Doe',
        'phoneCode' => '+91',
        'phoneNumber' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'productProfile' => ['4-wheelers'],
        'selectedStalls' => ['35'],
    ]);

    Livewire::test(Confirmation::class, ['exhibition' => $exhibition])
        ->assertStatus(200)
        ->assertSee('Test Motors')
        ->assertSee('John Doe')
        ->assertSee('john@testmotors.com');
});

it('displays line items and pricing', function () {
    $exhibition = Exhibition::factory()->create();

    session()->put('booking_data', [
        'exhibitionId' => $exhibition->id,
        'brandName' => 'Test Motors',
        'contactPerson' => 'John Doe',
        'phoneCode' => '+91',
        'phoneNumber' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'productProfile' => ['4-wheelers'],
        'selectedStalls' => ['35', '47'],
    ]);

    Livewire::test(Confirmation::class, ['exhibition' => $exhibition])
        ->assertStatus(200)
        ->assertSee('35')
        ->assertSee('47')
        ->assertSee('GST (18%)');
});

it('can go back to booking form', function () {
    $exhibition = Exhibition::factory()->create();

    session()->put('booking_data', [
        'exhibitionId' => $exhibition->id,
        'brandName' => 'Test Motors',
        'contactPerson' => 'John Doe',
        'phoneCode' => '+91',
        'phoneNumber' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'productProfile' => ['4-wheelers'],
        'selectedStalls' => ['35'],
    ]);

    Livewire::test(Confirmation::class, ['exhibition' => $exhibition])
        ->call('goBack')
        ->assertRedirect(route('exhibitions.booking.show', $exhibition));
});

it('can confirm booking', function () {
    $exhibition = Exhibition::factory()->create();

    session()->put('booking_data', [
        'exhibitionId' => $exhibition->id,
        'brandName' => 'Test Motors',
        'contactPerson' => 'John Doe',
        'phoneCode' => '+91',
        'phoneNumber' => '98765 43210',
        'email' => 'john@testmotors.com',
        'city' => 'Surat',
        'productProfile' => ['4-wheelers'],
        'hasExhibitedBefore' => false,
        'participationYears' => [],
        'isSgcciMember' => false,
        'membershipType' => '',
        'selectedStalls' => ['35'],
    ]);

    Livewire::test(Confirmation::class, ['exhibition' => $exhibition])
        ->call('confirm')
        ->assertHasNoErrors();

    expect(\App\Models\Booking::where('email', 'john@testmotors.com')->exists())->toBeTrue();
});
