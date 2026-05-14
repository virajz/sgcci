<?php

declare(strict_types=1);

use App\Livewire\ExhibitionSelector;
use App\Models\Exhibition;
use App\Models\User;
use App\Services\CurrentExhibition;
use Livewire\Livewire;

it('preloads the currently selected exhibition', function () {
    $exhibition = Exhibition::factory()->create();
    CurrentExhibition::set($exhibition->id);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSelector::class)
        ->assertSet('selectedId', (string) $exhibition->id);
});

it('persists the selection in the session when changed', function () {
    $first = Exhibition::factory()->create();
    $second = Exhibition::factory()->create();
    CurrentExhibition::set($first->id);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ExhibitionSelector::class)
        ->set('selectedId', (string) $second->id);

    expect(CurrentExhibition::id())->toBe($second->id);
});
