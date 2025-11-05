<?php

use App\Livewire\Admin\Inquiries\Index;
use App\Models\Booking;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->admin);
});

it('can search by brand name', function () {
    // Create bookings with different brand names
    Booking::factory()->create(['brand_name' => 'Tesla Motors']);
    Booking::factory()->create(['brand_name' => 'Ford Dealership']);
    Booking::factory()->create(['brand_name' => 'Honda Showroom']);

    Livewire::test(Index::class)
        ->set('search', 'Tesla')
        ->assertSee('Tesla Motors')
        ->assertDontSee('Ford Dealership')
        ->assertDontSee('Honda Showroom');
});

it('can search by booking code', function () {
    $booking1 = Booking::factory()->create();
    $booking2 = Booking::factory()->create();

    Livewire::test(Index::class)
        ->set('search', $booking1->booking_code)
        ->assertSee($booking1->booking_code)
        ->assertDontSee($booking2->booking_code);
});

it('can search by contact person', function () {
    Booking::factory()->create(['contact_person' => 'John Doe']);
    Booking::factory()->create(['contact_person' => 'Jane Smith']);

    Livewire::test(Index::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

it('can search by email', function () {
    Booking::factory()->create(['email' => 'john@tesla.com', 'brand_name' => 'Tesla']);
    Booking::factory()->create(['email' => 'jane@ford.com', 'brand_name' => 'Ford']);

    Livewire::test(Index::class)
        ->set('search', 'tesla.com')
        ->assertSee('Tesla')
        ->assertDontSee('Ford');
});

it('can search by phone number', function () {
    Booking::factory()->create([
        'phone_number' => '78749 49091',
        'brand_name' => 'Tesla Motors',
    ]);
    Booking::factory()->create([
        'phone_number' => '98765 43210',
        'brand_name' => 'Ford Motors',
    ]);

    // Search by partial phone number
    Livewire::test(Index::class)
        ->set('search', '9490')
        ->assertSee('Tesla Motors')
        ->assertDontSee('Ford Motors');
});

it('can search by full phone number', function () {
    Booking::factory()->create([
        'phone_number' => '78749 49091',
        'brand_name' => 'Tesla Motors',
    ]);
    Booking::factory()->create([
        'phone_number' => '98765 43210',
        'brand_name' => 'Ford Motors',
    ]);

    Livewire::test(Index::class)
        ->set('search', '78749 49091')
        ->assertSee('Tesla Motors')
        ->assertDontSee('Ford Motors');
});

it('search is case insensitive', function () {
    Booking::factory()->create(['brand_name' => 'Tesla Motors']);
    Booking::factory()->create(['brand_name' => 'Ford Motors']);

    Livewire::test(Index::class)
        ->set('search', 'TESLA')
        ->assertSee('Tesla Motors')
        ->assertDontSee('Ford Motors');
});

it('can clear search', function () {
    Booking::factory()->create(['brand_name' => 'Tesla Motors']);
    Booking::factory()->create(['brand_name' => 'Ford Motors']);

    Livewire::test(Index::class)
        ->set('search', 'Tesla')
        ->assertSee('Tesla Motors')
        ->assertDontSee('Ford Motors')
        ->set('search', '')
        ->assertSee('Tesla Motors')
        ->assertSee('Ford Motors');
});

it('search works with status filter', function () {
    Booking::factory()->create([
        'brand_name' => 'Tesla Motors',
        'status' => 'pending_approval',
    ]);
    Booking::factory()->create([
        'brand_name' => 'Ford Motors',
        'status' => 'payment_completed',
    ]);
    Booking::factory()->create([
        'brand_name' => 'Tesla Dealership',
        'status' => 'payment_completed',
    ]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'payment_completed')
        ->set('search', 'Tesla')
        ->assertSee('Tesla Dealership')
        ->assertDontSee('Tesla Motors')
        ->assertDontSee('Ford Motors');
});
