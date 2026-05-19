<?php

declare(strict_types=1);

use App\Livewire\Admin\WalkInVisitors\Index;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use App\Services\CurrentExhibition;
use App\VisitorRegistrationStatus;
use App\VisitorType;
use Livewire\Livewire;

beforeEach(function () {
    $this->exhibition = Exhibition::factory()->create();
    CurrentExhibition::set($this->exhibition->id);
    $this->admin = User::factory()->admin()->create();
});

it('creates a confirmed PRESS walk-in with no name or phone', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('createPressVisitor');

    $visitor = ExhibitionVisitor::first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->visitor_type)->toBe(VisitorType::Press)
        ->and($visitor->status)->toBe(VisitorRegistrationStatus::Confirmed)
        ->and($visitor->source)->toBe('front_desk')
        ->and($visitor->exhibition_id)->toBe($this->exhibition->id)
        ->and($visitor->name)->toBe('')
        ->and($visitor->phone_number)->toBe('')
        ->and($visitor->registration_code)->toStartWith('PRESS-');
});

it('creates a confirmed VIP walk-in with no name or phone', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('createVipVisitor');

    $visitor = ExhibitionVisitor::first();

    expect($visitor->visitor_type)->toBe(VisitorType::Vip)
        ->and($visitor->registration_code)->toStartWith('VIP-');
});

it('refuses to create quick walk-ins when no exhibition is selected', function () {
    CurrentExhibition::clear();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('createPressVisitor');

    expect(ExhibitionVisitor::count())->toBe(0);
});

it('dispatches a print window event with selected registration codes', function () {
    $a = ExhibitionVisitor::factory()->create(['exhibition_id' => $this->exhibition->id, 'source' => 'front_desk']);
    $b = ExhibitionVisitor::factory()->create(['exhibition_id' => $this->exhibition->id, 'source' => 'front_desk']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('selectedIds', [$a->id, $b->id])
        ->call('printSelected')
        ->assertDispatched(
            'open-print-window',
            url: route('admin.walk-in-visitors.print-badges', [
                'codes' => $a->registration_code.','.$b->registration_code,
            ]),
        );
});

it('does not dispatch print event when nothing is selected', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('printSelected')
        ->assertNotDispatched('open-print-window');
});

it('renders the print page for selected codes', function () {
    $a = ExhibitionVisitor::factory()->create();
    $b = ExhibitionVisitor::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.walk-in-visitors.print-badges', [
            'codes' => $a->registration_code.','.$b->registration_code,
        ]))
        ->assertOk()
        ->assertSee($a->registration_code, escape: false)
        ->assertSee($b->registration_code, escape: false);
});

it('aborts the print page when codes are missing', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.walk-in-visitors.print-badges'))
        ->assertNotFound();
});
