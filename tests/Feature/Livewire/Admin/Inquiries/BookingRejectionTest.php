<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Jobs\SendWhatsAppCampaign;
use App\Livewire\Admin\Inquiries\Show;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

test('super admin can reject booking with reason', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->call('openRejectModal')
        ->set('rejectionReason', 'Requested stalls are not available for this exhibition')
        ->call('reject')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Rejected)
        ->and($booking->rejection_reason)->toBe('Requested stalls are not available for this exhibition')
        ->and($booking->rejected_by)->toBe($superAdmin->id)
        ->and($booking->rejected_at)->not->toBeNull();
});

test('rejection sends whatsapp notification with support ticket link', function () {
    Config::set('services.whatsapp.enabled', true);
    Bus::fake();

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create(['title' => 'Auto Expo 2025']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'contact_person' => 'John Doe',
        'booking_code' => 'ABC12345',
        'selected_stalls' => ['101', '102'],
        'phone_code' => '+91',
        'phone_number' => '9876543210',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('rejectionReason', 'Stalls not available')
        ->call('reject')
        ->assertHasNoErrors();

    Bus::assertDispatched(SendWhatsAppCampaign::class, function ($job) use ($booking) {
        $expectedUrl = route('support-tickets.create', ['ticket' => $booking->booking_code]);

        return $job->campaignName === 'bookingrejected'
            && $job->phoneCode === '+91'
            && $job->phoneNumber === '9876543210'
            && $job->templateParams[0] === 'John Doe'
            && $job->templateParams[1] === 'Auto Expo 2025'
            && $job->templateParams[2] === 'ABC12345'
            && $job->templateParams[3] === '101, 102'
            && $job->templateParams[4] === 'Stalls not available'
            && $job->templateParams[5] === $expectedUrl;
    });
});

test('only super admin can reject bookings', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('rejectionReason', 'Test rejection')
        ->call('reject')
        ->assertHasNoErrors();

    $booking->refresh();

    // Status should not change
    expect($booking->status)->toBe(BookingStatus::PendingApproval)
        ->and($booking->rejected_at)->toBeNull();
});

test('rejection requires minimum 10 character reason', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('rejectionReason', 'Too short')
        ->call('reject')
        ->assertHasErrors(['rejectionReason']);

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::PendingApproval);
});

test('support ticket url contains booking code', function () {
    Config::set('services.whatsapp.enabled', true);
    Bus::fake();

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
        'booking_code' => 'TEST1234',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('rejectionReason', 'Testing support ticket link')
        ->call('reject');

    Bus::assertDispatched(SendWhatsAppCampaign::class, function ($job) {
        return str_contains($job->templateParams[5], 'ticket=TEST1234');
    });
});

test('no whatsapp sent when whatsapp is disabled', function () {
    Config::set('services.whatsapp.enabled', false);
    Bus::fake();

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PendingApproval,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('rejectionReason', 'Test rejection without WhatsApp')
        ->call('reject');

    Bus::assertNotDispatched(SendWhatsAppCampaign::class);
});

test('admin can release stalls and customer receives notification', function () {
    Config::set('services.whatsapp.enabled', true);
    Bus::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create(['title' => 'Auto Expo 2025']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
        'contact_person' => 'Jane Doe',
        'booking_code' => 'XYZ98765',
        'selected_stalls' => ['201', '202'],
        'phone_code' => '+91',
        'phone_number' => '9123456789',
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('releaseReason', 'Customer requested cancellation')
        ->call('releaseStalls')
        ->assertHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->rejection_reason)->toBe('Customer requested cancellation')
        ->and($booking->rejected_by)->toBe($admin->id)
        ->and($booking->rejected_at)->not->toBeNull();

    Bus::assertDispatched(SendWhatsAppCampaign::class, function ($job) use ($booking) {
        $expectedUrl = route('support-tickets.create', ['ticket' => $booking->booking_code]);

        return $job->campaignName === 'bookingrejected'
            && $job->phoneCode === '+91'
            && $job->phoneNumber === '9123456789'
            && $job->templateParams[0] === 'Jane Doe'
            && $job->templateParams[1] === 'Auto Expo 2025'
            && $job->templateParams[2] === 'XYZ98765'
            && $job->templateParams[3] === '201, 202'
            && $job->templateParams[4] === 'Customer requested cancellation'
            && $job->templateParams[5] === $expectedUrl;
    });
});

test('release stalls requires minimum 10 character reason', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentPending,
    ]);

    $this->actingAs($admin);

    Livewire::test(Show::class, ['booking' => $booking])
        ->set('releaseReason', 'Short')
        ->call('releaseStalls')
        ->assertHasErrors(['releaseReason']);

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::PaymentPending);
});
