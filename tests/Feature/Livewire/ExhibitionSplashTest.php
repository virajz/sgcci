<?php

declare(strict_types=1);

use App\Livewire\ExhibitionSplash;
use App\Models\Exhibition;
use App\Models\User;
use App\Services\CurrentExhibition;
use Livewire\Livewire;

it('opens for an admin when no exhibition has been selected', function () {
    Exhibition::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSplash::class)
        ->assertSet('open', true);
});

it('stays closed for an admin once an exhibition is selected', function () {
    $exhibition = Exhibition::factory()->create();
    CurrentExhibition::set($exhibition->id);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSplash::class)
        ->assertSet('open', false);
});

it('stays closed for non-admin, non-front-desk users', function () {
    Exhibition::factory()->create();

    Livewire::actingAs(User::factory()->create(['role' => 'exhibitor']))
        ->test(ExhibitionSplash::class)
        ->assertSet('open', false);
});

it('opens for a front-desk user when no exhibition has been selected', function () {
    Exhibition::factory()->create();

    Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(ExhibitionSplash::class)
        ->assertSet('open', true);
});

it('stays closed when no exhibitions exist', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSplash::class)
        ->assertSet('open', false);
});

it('saves the selection on confirm', function () {
    $exhibition = Exhibition::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSplash::class)
        ->set('selectedId', (string) $exhibition->id)
        ->call('confirm');

    expect(CurrentExhibition::id())->toBe($exhibition->id);
});

it('rejects a missing exhibition', function () {
    Exhibition::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSplash::class)
        ->set('selectedId', '999999')
        ->call('confirm')
        ->assertHasErrors(['selectedId']);

    expect(CurrentExhibition::id())->toBeNull();
});
