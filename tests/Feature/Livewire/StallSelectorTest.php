<?php

use App\Livewire\StallSelector;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(StallSelector::class)
        ->assertStatus(200);
});

it('can select a stall', function () {
    Livewire::test(StallSelector::class)
        ->assertSet('selectedStalls', [])
        ->call('toggleStall', '35')
        ->assertSet('selectedStalls', ['35'])
        ->assertDispatched('stalls-selected', selectedStalls: ['35']);
});

it('can select multiple stalls', function () {
    Livewire::test(StallSelector::class)
        ->call('toggleStall', '35')
        ->assertSet('selectedStalls', ['35'])
        ->call('toggleStall', '47')
        ->assertSet('selectedStalls', ['35', '47'])
        ->call('toggleStall', '59')
        ->assertSet('selectedStalls', ['35', '47', '59'])
        ->assertDispatched('stalls-selected', selectedStalls: ['35', '47', '59']);
});

it('can deselect a stall', function () {
    Livewire::test(StallSelector::class)
        ->call('toggleStall', '35')
        ->call('toggleStall', '47')
        ->assertSet('selectedStalls', ['35', '47'])
        ->call('toggleStall', '35')
        ->assertSet('selectedStalls', ['47'])
        ->assertDispatched('stalls-selected', selectedStalls: ['47']);
});

it('can clear all selected stalls', function () {
    Livewire::test(StallSelector::class)
        ->call('toggleStall', '35')
        ->call('toggleStall', '47')
        ->call('toggleStall', '59')
        ->assertSet('selectedStalls', ['35', '47', '59'])
        ->call('clearStalls')
        ->assertSet('selectedStalls', [])
        ->assertDispatched('stalls-selected', selectedStalls: []);
});

it('can clear stalls via event', function () {
    Livewire::test(StallSelector::class)
        ->call('toggleStall', '35')
        ->call('toggleStall', '47')
        ->assertSet('selectedStalls', ['35', '47'])
        ->dispatch('clear-stalls')
        ->assertSet('selectedStalls', [])
        ->assertDispatched('stalls-selected', selectedStalls: []);
});
