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
