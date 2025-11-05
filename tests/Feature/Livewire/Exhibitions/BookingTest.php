<?php

use App\Livewire\Exhibitions\Booking;
use App\Models\Exhibition;
use Livewire\Livewire;

it('renders booking form successfully', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->assertStatus(200);
});

it('can receive stall selection from stall selector', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->assertSet('selectedStalls', [])
        ->dispatch('stalls-selected', selectedStalls: ['35'])
        ->assertSet('selectedStalls', ['35']);
});

it('displays selected stalls in the form', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->dispatch('stalls-selected', selectedStalls: ['47'])
        ->assertSet('selectedStalls', ['47'])
        ->assertSee('Selected Stalls (1)')
        ->assertSee('47');
});

it('can display multiple selected stalls', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->dispatch('stalls-selected', selectedStalls: ['35', '47', '59'])
        ->assertSet('selectedStalls', ['35', '47', '59'])
        ->assertSee('Selected Stalls (3)')
        ->assertSee('35')
        ->assertSee('47')
        ->assertSee('59');
});

it('can update selected stalls', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->dispatch('stalls-selected', selectedStalls: ['35', '47'])
        ->assertSet('selectedStalls', ['35', '47'])
        ->dispatch('stalls-selected', selectedStalls: ['35'])
        ->assertSet('selectedStalls', ['35'])
        ->assertSee('Selected Stalls (1)');
});

it('can clear selected stalls from booking form', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->dispatch('stalls-selected', selectedStalls: ['35', '47', '59'])
        ->assertSet('selectedStalls', ['35', '47', '59'])
        ->call('clearSelectedStalls')
        ->assertSet('selectedStalls', [])
        ->assertDispatched('clear-stalls')
        ->assertDontSee('Selected Stalls');
});

it('restores selected stalls from session when returning from confirmation', function () {
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
        'selectedStalls' => ['35', '37'],
    ]);

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->assertSet('selectedStalls', ['35', '37'])
        ->assertSee('Selected Stalls (2)')
        ->assertSee('35')
        ->assertSee('37');
});

it('requires custom city when Others is selected', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@test.com')
        ->set('city', 'Others')
        ->set('customCity', '')
        ->set('productProfile', ['4-wheelers'])
        ->set('spaceType', 'standard')
        ->set('selectedStalls', ['A1'])
        ->call('save')
        ->assertHasErrors(['customCity']);
});

it('saves custom city when Others is selected', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::test(Booking::class, ['exhibition' => $exhibition])
        ->set('brandName', 'Test Motors')
        ->set('contactPerson', 'John Doe')
        ->set('phoneNumber', '98765 43210')
        ->set('email', 'john@test.com')
        ->set('city', 'Others')
        ->set('customCity', 'Jaipur')
        ->set('productProfile', ['4-wheelers'])
        ->set('spaceType', 'standard')
        ->set('selectedStalls', ['A1'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(session('booking_data.city'))->toBe('Jaipur');
});
