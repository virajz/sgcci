<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->exhibition = Exhibition::factory()->create();
});

test('admin can block stalls manually', function () {
    actingAs($this->admin);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', $this->exhibition->id)
        ->set('stallNumbers', '101, 102, 103')
        ->call('blockStalls')
        ->assertDispatched('stalls-blocked')
        ->assertDispatched('refresh-inquiries');

    expect(Booking::where('is_manual_block', true)->count())->toBe(3);
    expect(Booking::where('is_manual_block', true)->pluck('selected_stalls')->flatten()->toArray())
        ->toMatchArray(['101', '102', '103']);
});

test('blocked stalls have correct attributes', function () {
    actingAs($this->admin);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', $this->exhibition->id)
        ->set('stallNumbers', '101')
        ->call('blockStalls');

    $booking = Booking::where('is_manual_block', true)->first();

    expect($booking->is_manual_block)->toBeTrue()
        ->and($booking->blocked_by)->toBe($this->admin->id)
        ->and($booking->blocked_at)->not->toBeNull()
        ->and($booking->status->value)->toBe('payment_completed')
        ->and($booking->brand_name)->toBe('Manual Block');
});

test('admin cannot block already booked stalls', function () {
    actingAs($this->admin);

    // Create existing booking
    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['101'],
        'status' => 'payment_completed',
    ]);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', $this->exhibition->id)
        ->set('stallNumbers', '101, 102')
        ->call('blockStalls');

    // Only stall 102 should be blocked (101 was already booked)
    expect(Booking::where('is_manual_block', true)->count())->toBe(1);
    $blockedStall = Booking::where('is_manual_block', true)->first();
    expect($blockedStall->selected_stalls)->toMatchArray(['102']);
});

test('admin can release blocked stalls', function () {
    actingAs($this->admin);

    $booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['101'],
        'is_manual_block' => true,
        'blocked_by' => $this->admin->id,
        'blocked_at' => now(),
        'status' => 'payment_completed',
    ]);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', $this->exhibition->id)
        ->call('releaseStall', $booking->id)
        ->assertDispatched('stall-released')
        ->assertDispatched('refresh-inquiries');

    expect(Booking::find($booking->id))->toBeNull();
});

test('release stalls modal displays blocked stalls', function () {
    actingAs($this->admin);

    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['101', '102'],
        'is_manual_block' => true,
        'blocked_by' => $this->admin->id,
        'blocked_at' => now(),
        'status' => 'payment_completed',
    ]);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', $this->exhibition->id)
        ->assertSee('101')
        ->assertSee('102');
});

test('validates stall numbers input', function () {
    actingAs($this->admin);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', $this->exhibition->id)
        ->set('stallNumbers', '')
        ->call('blockStalls')
        ->assertHasErrors(['stallNumbers']);
});

test('validates exhibition id is required', function () {
    actingAs($this->admin);

    Volt::test('admin.stall-block-manager')
        ->set('exhibitionId', null)
        ->set('stallNumbers', '101')
        ->call('blockStalls')
        ->assertHasErrors(['exhibitionId']);
});

test('blocked stalls appear as allotted in stall selector', function () {
    Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'selected_stalls' => ['101'],
        'is_manual_block' => true,
        'blocked_by' => $this->admin->id,
        'blocked_at' => now(),
        'status' => 'payment_completed',
    ]);

    Volt::test('stall-selector')
        ->set('exhibitionId', $this->exhibition->id)
        ->assertSet('bookedStalls', fn ($stalls) => $stalls['101'] === 'allotted');
});
