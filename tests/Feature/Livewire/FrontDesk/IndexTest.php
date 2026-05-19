<?php

declare(strict_types=1);

use App\Livewire\FrontDesk\Index;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use App\Services\CurrentExhibition;
use Livewire\Livewire;

it('registers walk-in visitors against the selected exhibition, not the latest', function () {
    $older = Exhibition::factory()->create(['start_date' => now()->subMonths(2)]);
    Exhibition::factory()->create(['start_date' => now()->addMonths(2)]); // latest

    CurrentExhibition::set($older->id);

    Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class)
        ->set('activeTab', 'add')
        ->set('phoneNumber', '9876543210')
        ->set('name', 'Test Visitor')
        ->set('state', 'Gujarat')
        ->set('city', 'Surat')
        ->call('registerWalkIn')
        ->assertSet('addSuccess', true);

    $visitor = ExhibitionVisitor::where('phone_number', '9876543210')->first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->exhibition_id)->toBe($older->id);
});

it('falls back to the latest exhibition when no exhibition is selected', function () {
    Exhibition::factory()->create([
        'start_date' => now()->subMonths(2),
        'created_at' => now()->subDay(),
    ]);
    $latest = Exhibition::factory()->create([
        'start_date' => now()->addMonths(2),
        'created_at' => now(),
    ]);

    Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class)
        ->set('activeTab', 'add')
        ->set('phoneNumber', '9123456780')
        ->set('name', 'Fallback Visitor')
        ->set('state', 'Gujarat')
        ->set('city', 'Surat')
        ->call('registerWalkIn')
        ->assertSet('addSuccess', true);

    $visitor = ExhibitionVisitor::where('phone_number', '9123456780')->first();

    expect($visitor->exhibition_id)->toBe($latest->id);
});
