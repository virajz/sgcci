<?php

declare(strict_types=1);

use App\Livewire\Admin\Inquiries\EditBooking;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Livewire\Livewire;

test('admin can access edit booking page', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create(['exhibition_id' => $exhibition->id]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.edit', $booking))
        ->assertSuccessful()
        ->assertSeeLivewire(EditBooking::class);
});

test('non-admin cannot access edit booking page', function () {
    $user = User::factory()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create(['exhibition_id' => $exhibition->id]);

    $this->actingAs($user)
        ->get(route('admin.inquiries.edit', $booking))
        ->assertForbidden();
});

test('guest cannot access edit booking page', function () {
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create(['exhibition_id' => $exhibition->id]);

    $this->get(route('admin.inquiries.edit', $booking))
        ->assertRedirect(route('login'));
});

test('admin can update booking details', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Original Brand',
        'contact_person' => 'Original Person',
        'email' => 'original@example.com',
        'city' => 'Surat',
        'product_profile' => ['4-wheelers'],
        'space_type' => 'standard',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditBooking::class, ['booking' => $booking])
        ->set('brandName', 'Updated Brand')
        ->set('contactPerson', 'Updated Person')
        ->set('email', 'updated@example.com')
        ->set('city', 'Mumbai')
        ->set('productProfile', ['4-wheelers', '2-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.inquiries.show', $booking));

    $booking->refresh();

    expect($booking->brand_name)->toBe('Updated Brand')
        ->and($booking->contact_person)->toBe('Updated Person')
        ->and($booking->email)->toBe('updated@example.com')
        ->and($booking->city)->toBe('Mumbai')
        ->and($booking->product_profile)->toBe(['4-wheelers', '2-wheelers']);
});

test('super admin can update booking details', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Original Brand',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(EditBooking::class, ['booking' => $booking])
        ->set('brandName', 'Updated by Super Admin')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->brand_name)->toBe('Updated by Super Admin');
});

test('admin cannot change selected stalls', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $originalStalls = ['A1', 'A2', 'A3'];
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'selected_stalls' => $originalStalls,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditBooking::class, ['booking' => $booking])
        ->set('brandName', 'Updated Brand')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->selected_stalls)->toBe($originalStalls);
});

test('pricing is recalculated when admin updates booking', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'selected_stalls' => ['A1', 'A2'],
        'has_exhibited_before' => false,
        'is_sgcci_member' => false,
        'space_type' => 'standard',
    ]);

    $originalTotal = $booking->total_with_gst;

    $this->actingAs($admin);

    Livewire::test(EditBooking::class, ['booking' => $booking])
        ->set('hasExhibitedBefore', true)
        ->set('participationYears', ['2019', '2024'])
        ->set('isSgcciMember', true)
        ->set('membershipType', 'premium-member')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    // Pricing should be different due to discounts
    expect($booking->total_with_gst)->not->toBe($originalTotal)
        ->and($booking->discount_percentage)->toBeGreaterThan(0);
});

test('admin edit validates required fields', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditBooking::class, ['booking' => $booking])
        ->set('brandName', '')
        ->set('email', 'invalid-email')
        ->set('productProfile', [])
        ->call('updateBooking')
        ->assertHasErrors(['brandName', 'email', 'productProfile']);
});

test('admin can change space type and pricing updates', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'selected_stalls' => ['A1', 'A2'],
        'space_type' => 'standard',
        'price_per_sqm' => 5000,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditBooking::class, ['booking' => $booking])
        ->set('spaceType', 'raw')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->space_type)->toBe('raw')
        ->and((float) $booking->price_per_sqm)->toBe(4500.0);
});

test('edit booking page shows selected stalls', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'selected_stalls' => ['A1', 'A2', 'B5'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.edit', $booking))
        ->assertSuccessful()
        ->assertSee('A1')
        ->assertSee('A2')
        ->assertSee('B5')
        ->assertSee('Selected Stalls (3)');
});

test('admin cannot edit booking with payment received', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'amount_paid' => 5000,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.edit', $booking))
        ->assertRedirect(route('admin.inquiries.show', $booking));
});

test('admin edit does not reset approval workflow', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'brand_name' => 'Original Brand',
        'product_profile' => ['4-wheelers'],
        'status' => \App\BookingStatus::PaymentPending,
        'admin_approved_by' => $admin->id,
        'admin_approved_at' => now(),
        'super_admin_approved_by' => $superAdmin->id,
        'super_admin_approved_at' => now(),
        'payment_link' => 'http://example.com/payment',
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\Inquiries\EditBooking::class, ['booking' => $booking])
        ->set('brandName', 'Updated Brand')
        ->set('productProfile', ['4-wheelers'])
        ->call('updateBooking')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.inquiries.show', $booking));

    $booking->refresh();

    // Admin edits should NOT reset approval workflow
    expect($booking->status)->toBe(\App\BookingStatus::PaymentPending)
        ->and($booking->admin_approved_by)->toBe($admin->id)
        ->and($booking->admin_approved_at)->not->toBeNull()
        ->and($booking->super_admin_approved_by)->toBe($superAdmin->id)
        ->and($booking->super_admin_approved_at)->not->toBeNull()
        ->and($booking->payment_link)->toBe('http://example.com/payment')
        ->and($booking->brand_name)->toBe('Updated Brand');
});
