<?php

declare(strict_types=1);

use App\Livewire\FrontDesk\Index;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use App\VisitorRegistrationStatus;
use Livewire\Livewire;

it('registers walk-in visitors against the exhibition picked in the walk-in form', function () {
    $earlier = Exhibition::factory()->create(['start_date' => now()->subMonths(2)]);
    $later = Exhibition::factory()->create(['start_date' => now()->addMonths(2)]);

    Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class)
        ->set('walkInExhibitionId', (string) $later->id)
        ->set('phoneNumber', '9876543210')
        ->set('name', 'Test Visitor')
        ->set('state', 'Gujarat')
        ->set('city', 'Surat')
        ->call('registerWalkIn')
        ->assertSet('addSuccess', true);

    $visitor = ExhibitionVisitor::where('phone_number', '9876543210')->first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->exhibition_id)->toBe($later->id);
});

it('defaults the walk-in exhibition to the earliest non-closed exhibition', function () {
    $earliest = Exhibition::factory()->create(['start_date' => now()->subMonths(2)]);
    Exhibition::factory()->create(['start_date' => now()->addMonths(2)]);

    Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class)
        ->assertSet('walkInExhibitionId', (string) $earliest->id);
});

it('does not include closed exhibitions in the walk-in selection list', function () {
    $open = Exhibition::factory()->create(['start_date' => now()->subMonths(1), 'registration_closed' => false]);
    $closed = Exhibition::factory()->create(['start_date' => now()->subMonths(3), 'registration_closed' => true]);

    $component = Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class);

    $ids = $component->instance()->openExhibitions->pluck('id')->all();

    expect($ids)->toContain($open->id)
        ->and($ids)->not->toContain($closed->id);
});

it('looks up a visitor across non-closed exhibitions and exposes the exhibition info', function () {
    $exhibition = Exhibition::factory()->create(['title' => 'Spring Expo', 'registration_closed' => false]);

    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => VisitorRegistrationStatus::Confirmed,
    ]);

    $component = Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class)
        ->set('lookupCode', $visitor->registration_code)
        ->call('lookup');

    $component->assertSet('foundVisitor.registration_code', $visitor->registration_code)
        ->assertSet('foundVisitor.exhibition_title', 'Spring Expo');
});

it('does not find visitors in closed exhibitions', function () {
    $closed = Exhibition::factory()->create(['registration_closed' => true]);

    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $closed->id,
        'status' => VisitorRegistrationStatus::Confirmed,
    ]);

    Livewire::actingAs(User::factory()->create(['role' => 'front_desk']))
        ->test(Index::class)
        ->set('lookupCode', $visitor->registration_code)
        ->call('lookup')
        ->assertSet('foundVisitor', null)
        ->assertSet('foundMember', null);
});
