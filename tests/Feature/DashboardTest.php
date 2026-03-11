<?php

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertSuccessful();
});

test('admin dashboard displays all stat cards', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('Available Stalls')
        ->assertSee('Payments Due Soon')
        ->assertSee('Confirmed Bookings')
        ->assertSee('Pending Reviews')
        ->assertSee('Visitors Registered');
});

test('exhibitor dashboard shows leads and whatsapp inquiries stats', function () {
    $user = User::factory()->create(['role' => 'exhibitor']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('Leads Captured')
        ->assertSee('WhatsApp Inquiries')
        ->assertDontSee('Available Stalls')
        ->assertDontSee('Payments Due Soon')
        ->assertDontSee('Confirmed Bookings')
        ->assertDontSee('Pending Reviews')
        ->assertDontSee('Visitor Payments Collected');
});

test('dashboard shows correct available stalls count', function () {
    $user = User::factory()->create();
    $exhibition = Exhibition::factory()->create();

    // Create 3 completed bookings with 1 stall each
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'selected_stalls' => ['1'],
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'selected_stalls' => ['2'],
    ]);

    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'selected_stalls' => ['3'],
    ]);

    // Total stalls = 93, booked = 3, available = 90
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('90'); // Available stalls
});

test('dashboard shows correct payments due count', function () {
    $user = User::factory()->create();
    $exhibition = Exhibition::factory()->create();

    // Create bookings with payment due in 2 days
    Booking::factory()->count(2)->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addDays(2),
    ]);

    // Create booking with payment due in 5 days (should not count)
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'payment_due_at' => now()->addDays(5),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('2'); // Payments due soon
});

test('dashboard shows correct confirmed bookings count', function () {
    $user = User::factory()->create();
    $exhibition = Exhibition::factory()->create();

    // Create 5 completed bookings
    Booking::factory()->count(5)->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
    ]);

    // Create pending booking (should not count)
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('5'); // Confirmed bookings
});

test('dashboard shows correct pending reviews count', function () {
    $user = User::factory()->create();
    $exhibition = Exhibition::factory()->create();

    // Create 4 pending approval bookings
    Booking::factory()->count(4)->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    // Create approved booking (should not count)
    Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::ApprovedByAdmin,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('4'); // Pending reviews
});

test('dashboard stat cards link to inquiries page with correct filters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful()
        ->assertSee(route('admin.inquiries.index', ['tab' => 'all']))
        ->assertSee(route('admin.inquiries.index', ['tab' => 'payment_pending']))
        ->assertSee(route('admin.inquiries.index', ['tab' => 'payment_completed']))
        ->assertSee(route('admin.inquiries.index', ['tab' => 'pending_approval']));
});
