<?php

declare(strict_types=1);

use App\Livewire\SecurityDesk\Index;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->securityUser = User::factory()->create([
        'role' => 'security_desk',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->securityUser);

    $this->exhibition = Exhibition::factory()->create();

    $this->visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'registration_code' => 'VIS-ABC123',
        'status' => 'confirmed',
    ]);
});

it('extracts visitor code from a full URL when scanned and marks entry', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'https://sgcci.test/scan/VIS-ABC123')
        ->assertSet('lookupCode', '')  // reset after scan
        ->tap(fn ($c) => expect($c->get('scanLog'))->not->toBeEmpty())
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['type'])->toBe('entered'));
});

it('extracts invited guest code from a full URL — logs not_found (not a paid visitor)', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'https://sgcci.test/scan/INVIS-XYZ789')
        ->assertSet('lookupCode', '')
        ->tap(fn ($c) => expect($c->get('scanLog'))->not->toBeEmpty())
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['type'])->toBe('not_found'));
});

it('works normally when a bare visitor code is given', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'VIS-ABC123')
        ->assertSet('lookupCode', '')
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['type'])->toBe('entered'));
});

it('logs already_entered when visitor scans in again', function () {
    $this->visitor->update(['entered_at' => now()]);

    Livewire::test(Index::class)
        ->call('setLookupCode', 'VIS-ABC123')
        ->assertSet('lookupCode', '')
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['type'])->toBe('already_entered'))
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['duration'])->toBe(30));
});

it('logs not_found for an unknown code', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'VIS-UNKNOWN')
        ->assertSet('lookupCode', '')
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['type'])->toBe('not_found'))
        ->tap(fn ($c) => expect($c->get('scanLog')[0]['duration'])->toBe(5));
});

it('manual lookup still works and sets foundVisitor', function () {
    Livewire::test(Index::class)
        ->set('lookupCode', 'VIS-ABC123')
        ->call('lookup')
        ->assertSet('lookupPerformed', true)
        ->assertSet('foundVisitor.registration_code', 'VIS-ABC123');
});
