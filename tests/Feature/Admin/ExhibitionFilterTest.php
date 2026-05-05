<?php

use App\Livewire\Admin\Visitors\Index as VisitorsIndex;
use App\Livewire\Dashboard;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use Livewire\Livewire;

it('admin visitors page filters by exhibition', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $expoA = Exhibition::factory()->create(['title' => 'Expo A', 'start_date' => '2027-01-01', 'end_date' => '2027-01-05']);
    $expoB = Exhibition::factory()->create(['title' => 'Expo B', 'start_date' => '2027-02-01', 'end_date' => '2027-02-05']);

    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoA->id, 'name' => 'Alice From A']);
    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoA->id, 'name' => 'Allan From A']);
    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoB->id, 'name' => 'Bob From B']);

    Livewire::actingAs($admin)
        ->test(VisitorsIndex::class)
        ->assertSee('Alice From A')
        ->assertSee('Allan From A')
        ->assertSee('Bob From B')
        ->set('exhibitionFilter', (string) $expoA->id)
        ->assertSee('Alice From A')
        ->assertSee('Allan From A')
        ->assertDontSee('Bob From B');
});

it('admin visitors page reports active filter when an exhibition is chosen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create(['start_date' => '2027-01-01', 'end_date' => '2027-01-05']);

    $component = Livewire::actingAs($admin)
        ->test(VisitorsIndex::class)
        ->set('exhibitionFilter', (string) $exhibition->id);

    expect($component->instance()->hasActiveFilters())->toBeTrue();

    $component->call('clearFilters');
    expect($component->get('exhibitionFilter'))->toBe('');
});

it('dashboard scopes visitor counts to the chosen exhibition', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $expoA = Exhibition::factory()->create(['title' => 'Expo A', 'start_date' => '2027-01-01', 'end_date' => '2027-01-05']);
    $expoB = Exhibition::factory()->create(['title' => 'Expo B', 'start_date' => '2027-02-01', 'end_date' => '2027-02-05']);

    ExhibitionVisitor::factory()->count(3)->create(['exhibition_id' => $expoA->id, 'status' => 'confirmed']);
    ExhibitionVisitor::factory()->count(5)->create(['exhibition_id' => $expoB->id, 'status' => 'confirmed']);

    $component = Livewire::actingAs($admin)->test(Dashboard::class);

    expect($component->instance()->visitorsTotal)->toBe(8);

    $component->set('exhibitionId', (string) $expoA->id);
    expect($component->instance()->visitorsTotal)->toBe(3);

    $component->set('exhibitionId', (string) $expoB->id);
    expect($component->instance()->visitorsTotal)->toBe(5);

    $component->set('exhibitionId', '');
    expect($component->instance()->visitorsTotal)->toBe(8);
});
