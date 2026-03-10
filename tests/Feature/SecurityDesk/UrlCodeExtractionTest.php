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

it('extracts visitor code from a full URL when scanned', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'https://sgcci.test/scan/VIS-ABC123')
        ->assertSet('lookupCode', 'VIS-ABC123')
        ->assertSet('lookupPerformed', true);
});

it('extracts invited guest code from a full URL when scanned', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'https://sgcci.test/scan/INVIS-XYZ789')
        ->assertSet('lookupCode', 'INVIS-XYZ789')
        ->assertSet('lookupPerformed', true);
});

it('works normally when a bare visitor code is given', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'VIS-ABC123')
        ->assertSet('lookupCode', 'VIS-ABC123')
        ->assertSet('lookupPerformed', true);
});

it('finds the correct visitor after extracting code from URL', function () {
    Livewire::test(Index::class)
        ->call('setLookupCode', 'https://sgcci.test/scan/VIS-ABC123')
        ->assertSet('lookupCode', 'VIS-ABC123')
        ->assertSet('lookupPerformed', true)
        ->assertSet('foundVisitor.registration_code', 'VIS-ABC123');
});
