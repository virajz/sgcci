<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Livewire\Admin\Analytics\ExhibitorLeads;
use App\Models\Booking;
use App\Models\ExhibitionVisitor;
use App\Models\ExhibitorLead;
use App\Models\ExhibitorMemberLead;
use App\Models\User;
use App\Models\WhatsAppInquiry;
use Livewire\Livewire;

it('requires admin to access exhibitor leads analytics', function () {
    $user = User::factory()->create(['role' => 'exhibitor']);

    $this->actingAs($user)
        ->get(route('admin.analytics.exhibitor-leads'))
        ->assertForbidden();
});

it('allows admin to view exhibitor leads analytics page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.analytics.exhibitor-leads'))
        ->assertSuccessful();
});

it('shows exhibitor brand names with visitor lead counts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $booking = Booking::factory()->create([
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'Acme Pvt Ltd',
        'is_manual_block' => false,
    ]);

    $visitor = ExhibitionVisitor::factory()->create(['exhibition_id' => $booking->exhibition_id]);

    ExhibitorLead::create([
        'booking_id' => $booking->id,
        'exhibition_visitor_id' => $visitor->id,
        'person_index' => null,
        'captured_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->assertSee('Acme Pvt Ltd');
});

it('counts both visitor leads and member leads together', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $booking = Booking::factory()->create([
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'Test Brand',
        'is_manual_block' => false,
    ]);

    $visitor = ExhibitionVisitor::factory()->create(['exhibition_id' => $booking->exhibition_id]);

    ExhibitorLead::create([
        'booking_id' => $booking->id,
        'exhibition_visitor_id' => $visitor->id,
        'person_index' => null,
        'captured_at' => now(),
    ]);

    ExhibitorMemberLead::create([
        'booking_id' => $booking->id,
        'membership_number' => 'MEM001',
        'member_type' => 'sgcci',
        'member_name' => 'John Doe',
        'captured_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->assertSee('Test Brand')
        ->assertSee('2');
});

it('shows whatsapp inquiry count', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $booking = Booking::factory()->create([
        'status' => BookingStatus::PaymentCompleted,
        'brand_name' => 'WhatsApp Co',
        'is_manual_block' => false,
    ]);

    WhatsAppInquiry::factory()->count(3)->create(['booking_id' => $booking->id]);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->assertSee('WhatsApp Co')
        ->assertSee('3');
});

it('sorts by leads descending by default', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->assertSet('sortBy', 'leads')
        ->assertSet('sortDirection', 'desc');
});

it('can sort by name', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'desc');
});

it('toggles sort direction when sorting by same column again', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->call('sort', 'leads')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'leads')
        ->assertSet('sortDirection', 'desc');
});

it('can sort by whatsapp inquiries', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(ExhibitorLeads::class)
        ->call('sort', 'whatsapp')
        ->assertSet('sortBy', 'whatsapp')
        ->assertSet('sortDirection', 'desc');
});
