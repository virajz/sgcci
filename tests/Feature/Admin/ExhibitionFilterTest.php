<?php

use App\Livewire\Admin\Visitors\Index as VisitorsIndex;
use App\Livewire\Dashboard;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use App\Services\CurrentExhibition;
use Livewire\Livewire;

it('admin visitors page filters by the session exhibition', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $expoA = Exhibition::factory()->create(['title' => 'Expo A', 'start_date' => '2027-01-01', 'end_date' => '2027-01-05']);
    $expoB = Exhibition::factory()->create(['title' => 'Expo B', 'start_date' => '2027-02-01', 'end_date' => '2027-02-05']);

    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoA->id, 'name' => 'Alice From A']);
    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoA->id, 'name' => 'Allan From A']);
    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoB->id, 'name' => 'Bob From B']);

    CurrentExhibition::set($expoA->id);

    Livewire::actingAs($admin)
        ->test(VisitorsIndex::class)
        ->assertSee('Alice From A')
        ->assertSee('Allan From A')
        ->assertDontSee('Bob From B');
});

it('admin visitors page shows everything when no exhibition is selected', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $expoA = Exhibition::factory()->create(['title' => 'Expo A', 'start_date' => '2027-01-01', 'end_date' => '2027-01-05']);
    $expoB = Exhibition::factory()->create(['title' => 'Expo B', 'start_date' => '2027-02-01', 'end_date' => '2027-02-05']);

    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoA->id, 'name' => 'Alice From A']);
    ExhibitionVisitor::factory()->create(['exhibition_id' => $expoB->id, 'name' => 'Bob From B']);

    Livewire::actingAs($admin)
        ->test(VisitorsIndex::class)
        ->assertSee('Alice From A')
        ->assertSee('Bob From B');
});

it('dashboard scopes visitor counts to the session exhibition', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $expoA = Exhibition::factory()->create(['title' => 'Expo A', 'start_date' => '2027-01-01', 'end_date' => '2027-01-05']);
    $expoB = Exhibition::factory()->create(['title' => 'Expo B', 'start_date' => '2027-02-01', 'end_date' => '2027-02-05']);

    ExhibitionVisitor::factory()->count(3)->create(['exhibition_id' => $expoA->id, 'status' => 'confirmed']);
    ExhibitionVisitor::factory()->count(5)->create(['exhibition_id' => $expoB->id, 'status' => 'confirmed']);

    expect(Livewire::actingAs($admin)->test(Dashboard::class)->instance()->visitorsTotal)->toBe(8);

    CurrentExhibition::set($expoA->id);
    expect(Livewire::actingAs($admin)->test(Dashboard::class)->instance()->visitorsTotal)->toBe(3);

    CurrentExhibition::set($expoB->id);
    expect(Livewire::actingAs($admin)->test(Dashboard::class)->instance()->visitorsTotal)->toBe(5);

    CurrentExhibition::clear();
    expect(Livewire::actingAs($admin)->test(Dashboard::class)->instance()->visitorsTotal)->toBe(8);
});
