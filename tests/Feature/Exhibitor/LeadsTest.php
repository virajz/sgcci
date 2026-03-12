<?php

declare(strict_types=1);

use App\Livewire\Exhibitor\Leads;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\ExhibitorLead;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->exhibition = Exhibition::factory()->create();

    $this->exhibitorUser = User::factory()->create(['role' => 'exhibitor']);

    $this->booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'exhibitor_user_id' => $this->exhibitorUser->id,
    ]);
});

it('requires exhibitor role to access leads page', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('exhibitor.leads'))
        ->assertForbidden();
});

it('renders leads page for exhibitor', function () {
    $this->actingAs($this->exhibitorUser)
        ->get(route('exhibitor.leads'))
        ->assertSuccessful()
        ->assertSeeLivewire(Leads::class);
});

it('starts on capture tab by default', function () {
    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->assertSet('activeTab', 'capture');
});

it('shows empty state on list tab when no leads', function () {
    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->set('activeTab', 'list')
        ->assertSee('No leads yet');
});

it('can search for a visitor by registration code', function () {
    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->set('lookupCode', $visitor->registration_code)
        ->call('lookup')
        ->assertSet('lookupPerformed', true)
        ->assertSee($visitor->name);
});

it('can mark a visitor as a lead', function () {
    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->call('setLookupCode', $visitor->registration_code)
        ->call('markAsLead')
        ->assertSet('foundVisitor.is_lead', true);

    expect(
        ExhibitorLead::where('booking_id', $this->booking->id)
            ->where('exhibition_visitor_id', $visitor->id)
            ->exists()
    )->toBeTrue();
});

it('does not duplicate a lead when marking the same visitor twice', function () {
    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->call('setLookupCode', $visitor->registration_code)
        ->call('markAsLead')
        ->call('markAsLead');

    expect(
        ExhibitorLead::where('booking_id', $this->booking->id)
            ->where('exhibition_visitor_id', $visitor->id)
            ->count()
    )->toBe(1);
});

it('shows lead as already saved when already marked', function () {
    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    ExhibitorLead::create([
        'booking_id' => $this->booking->id,
        'exhibition_visitor_id' => $visitor->id,
        'captured_at' => now(),
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->call('setLookupCode', $visitor->registration_code)
        ->assertSet('foundVisitor.is_lead', true)
        ->assertSee('Lead');
});

it('shows leads in list tab', function () {
    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    ExhibitorLead::create([
        'booking_id' => $this->booking->id,
        'exhibition_visitor_id' => $visitor->id,
        'captured_at' => now(),
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->set('activeTab', 'list')
        ->assertSee($visitor->name)
        ->assertSee($visitor->phone_number);
});

it('only shows leads belonging to the current exhibitor', function () {
    $otherBooking = Booking::factory()->create(['exhibition_id' => $this->exhibition->id]);

    $otherVisitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'name' => 'Other Exhibitor Lead',
    ]);

    ExhibitorLead::create([
        'booking_id' => $otherBooking->id,
        'exhibition_visitor_id' => $otherVisitor->id,
        'captured_at' => now(),
    ]);

    $myVisitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'name' => 'My Lead',
    ]);

    ExhibitorLead::create([
        'booking_id' => $this->booking->id,
        'exhibition_visitor_id' => $myVisitor->id,
        'captured_at' => now(),
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->set('activeTab', 'list')
        ->assertSee('My Lead')
        ->assertDontSee('Other Exhibitor Lead');
});

it('multiple exhibitors can independently mark the same visitor as a lead', function () {
    $otherBooking = Booking::factory()->create(['exhibition_id' => $this->exhibition->id]);

    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    ExhibitorLead::create([
        'booking_id' => $this->booking->id,
        'exhibition_visitor_id' => $visitor->id,
        'captured_at' => now(),
    ]);

    ExhibitorLead::create([
        'booking_id' => $otherBooking->id,
        'exhibition_visitor_id' => $visitor->id,
        'captured_at' => now(),
    ]);

    expect(ExhibitorLead::where('exhibition_visitor_id', $visitor->id)->count())->toBe(2);
});

it('shows not found message for unknown code', function () {
    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->set('lookupCode', 'VIS-XXXXXX')
        ->call('lookup')
        ->assertSet('lookupPerformed', true)
        ->assertSet('foundVisitor', null)
        ->assertSee('Not found');
});

it('can reset lookup', function () {
    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
    ]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(Leads::class)
        ->call('setLookupCode', $visitor->registration_code)
        ->assertSet('lookupPerformed', true)
        ->call('resetLookup')
        ->assertSet('lookupPerformed', false)
        ->assertSet('foundVisitor', null)
        ->assertSet('lookupCode', '');
});
