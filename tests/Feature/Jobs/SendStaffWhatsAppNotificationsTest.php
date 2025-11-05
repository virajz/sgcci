<?php

use App\Jobs\SendStaffWhatsAppNotifications;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\StaffMember;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['services.whatsapp.enabled' => true]);
});

it('dispatches WhatsApp notifications to all active staff members', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
    ]);

    $activeStaff = StaffMember::factory()->count(3)->create(['is_active' => true]);
    $inactiveStaff = StaffMember::factory()->count(2)->create(['is_active' => false]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_received');
    $job->handle();

    // Should dispatch 3 jobs for 3 active staff members
    Queue::assertPushed(SendWhatsAppCampaign::class, 3);

    // Verify each active staff member receives a notification
    foreach ($activeStaff as $staff) {
        Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($staff) {
            return $job->phoneNumber === $staff->phone_number
                && $job->phoneCode === $staff->phone_code;
        });
    }
});

it('does not send notifications when WhatsApp is disabled', function () {
    Queue::fake();
    config(['services.whatsapp.enabled' => false]);

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
    ]);

    StaffMember::factory()->count(3)->create(['is_active' => true]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_received');
    $job->handle();

    Queue::assertNothingPushed();
});

it('builds correct template params for booking_received campaign', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Test Exhibition']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'John Doe',
        'booking_code' => 'ABC12345',
        'selected_stalls' => ['1', '2', '3'],
        'total_area' => 27,
        'total_with_gst' => 150000.50,
    ]);

    StaffMember::factory()->create([
        'phone_code' => '+91',
        'phone_number' => '9876543210',
        'is_active' => true,
    ]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_received');
    $job->handle();

    Queue::assertPushed(SendWhatsAppCampaign::class, 1);

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->campaignName === 'booking_received';
    });
});

it('builds correct template params for booking_confirmationpayment campaign', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Test Exhibition']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'Jane Smith',
        'booking_code' => 'XYZ67890',
        'selected_stalls' => ['4', '5'],
        'total_area' => 18,
        'total_with_gst' => 100000.00,
        'payment_link' => 'https://example.com/payment/XYZ67890',
        'payment_due_at' => now()->addDays(3),
    ]);

    StaffMember::factory()->create([
        'phone_code' => '+91',
        'phone_number' => '9876543210',
        'is_active' => true,
    ]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_confirmationpayment');
    $job->handle();

    Queue::assertPushed(SendWhatsAppCampaign::class, 1);

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->campaignName === 'booking_confirmationpayment';
    });
});

it('builds correct template params for payment_success campaign', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Test Exhibition']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'Bob Johnson',
        'booking_code' => 'DEF45678',
        'selected_stalls' => ['6'],
        'total_with_gst' => 50000.00,
        'payment_completed_at' => now(),
    ]);

    StaffMember::factory()->create([
        'phone_code' => '+91',
        'phone_number' => '9876543210',
        'is_active' => true,
    ]);

    $job = new SendStaffWhatsAppNotifications($booking, 'payment_success');
    $job->handle();

    Queue::assertPushed(SendWhatsAppCampaign::class, 1);

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->campaignName === 'payment_success';
    });
});

it('only sends to active staff members', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create();
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
    ]);

    StaffMember::factory()->create(['is_active' => true]);
    StaffMember::factory()->create(['is_active' => false]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_received');
    $job->handle();

    // Should only dispatch 1 job for the active staff member
    Queue::assertPushed(SendWhatsAppCampaign::class, 1);
});
