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
        return $job->campaignName === 'staff_booking_received';
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
        return $job->campaignName === 'staff_booking_confirmationpayment';
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
        return $job->campaignName === 'invoicestatus';
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

it('uses staff_booking_received template instead of booking_received', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Auto Expo 2025']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'John Doe',
        'booking_code' => 'TEST1234',
        'selected_stalls' => ['A1', 'A2', 'B3'],
        'total_area' => 45,
        'total_with_gst' => 67500.00,
    ]);

    $staff = StaffMember::factory()->create([
        'phone_code' => '+91',
        'phone_number' => '9876543210',
        'is_active' => true,
    ]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_received');
    $job->handle();

    // Verify staff template is used with correct campaign name
    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($staff) {
        return $job->campaignName === 'staff_booking_received'
            && $job->phoneNumber === $staff->phone_number;
    });
});

it('uses invoicestatus campaign for payment_success campaign', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Auto Expo 2025']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'Jane Smith',
        'booking_code' => 'PAY5678',
        'selected_stalls' => ['C1', 'C2'],
        'total_with_gst' => 85000.00,
        'payment_completed_at' => now(),
    ]);

    $staff = StaffMember::factory()->create([
        'phone_code' => '+91',
        'phone_number' => '9123456789',
        'is_active' => true,
    ]);

    $job = new SendStaffWhatsAppNotifications($booking, 'payment_success');
    $job->handle();

    // Verify invoicestatus campaign is used with correct campaign name
    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($staff) {
        return $job->campaignName === 'invoicestatus'
            && $job->phoneNumber === $staff->phone_number;
    });
});

it('sends invoicestatus with correct template parameters', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Industrial Fair 2025']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'Robert Brown',
        'booking_code' => 'FULL1234',
        'selected_stalls' => ['D1', 'D2', 'D3'],
        'total_with_gst' => 120000.00,
        'payment_completed_at' => now(),
    ]);

    $staff = StaffMember::factory()->count(2)->create(['is_active' => true]);

    $job = new SendStaffWhatsAppNotifications($booking, 'payment_success');
    $job->handle();

    // Should dispatch 2 jobs for 2 active staff members
    Queue::assertPushed(SendWhatsAppCampaign::class, 2);

    // Verify template params contain correct values
    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($booking) {
        return $job->campaignName === 'invoicestatus'
            && $job->templateParams[0] === $booking->contact_person
            && $job->templateParams[1] === $booking->exhibition->title
            && $job->templateParams[2] === $booking->booking_code
            && $job->templateParams[3] === number_format($booking->total_with_gst, 2)
            && $job->templateParams[5] === implode(', ', $booking->selected_stalls);
    });
});

it('uses staff_booking_confirmationpayment campaign for booking_confirmationpayment', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Tech Expo 2025']);
    $paymentDueAt = now()->addDays(3);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'Alice Johnson',
        'booking_code' => 'CONF5678',
        'selected_stalls' => ['E1', 'E2'],
        'total_area' => 30,
        'total_with_gst' => 95000.00,
        'payment_link' => 'https://example.com/payment/CONF5678',
        'payment_due_at' => $paymentDueAt,
    ]);

    $staff = StaffMember::factory()->create([
        'phone_code' => '+91',
        'phone_number' => '9988776655',
        'is_active' => true,
    ]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_confirmationpayment');
    $job->handle();

    // Verify staff_booking_confirmationpayment campaign is used
    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($staff) {
        return $job->campaignName === 'staff_booking_confirmationpayment'
            && $job->phoneNumber === $staff->phone_number;
    });
});

it('sends staff_booking_confirmationpayment with correct template parameters', function () {
    Queue::fake();

    $exhibition = Exhibition::factory()->create(['title' => 'Electronics Fair 2025']);
    $paymentDueAt = now()->addDays(5);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'contact_person' => 'Michael Chen',
        'booking_code' => 'BOOK9999',
        'selected_stalls' => ['F1', 'F2', 'F3'],
        'total_area' => 45,
        'total_with_gst' => 150000.00,
        'payment_link' => 'https://sgcci.test/payment/BOOK9999',
        'payment_due_at' => $paymentDueAt,
    ]);

    $staff = StaffMember::factory()->count(3)->create(['is_active' => true]);

    $job = new SendStaffWhatsAppNotifications($booking, 'booking_confirmationpayment');
    $job->handle();

    // Should dispatch 3 jobs for 3 active staff members
    Queue::assertPushed(SendWhatsAppCampaign::class, 3);

    // Verify template params contain correct values
    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($booking, $paymentDueAt) {
        return $job->campaignName === 'staff_booking_confirmationpayment'
            && $job->templateParams[0] === $booking->contact_person
            && $job->templateParams[1] === $booking->exhibition->title
            && $job->templateParams[2] === implode(', ', $booking->selected_stalls)
            && $job->templateParams[3] === $booking->booking_code
            && $job->templateParams[4] === number_format($booking->total_area, 0)
            && $job->templateParams[5] === number_format($booking->total_with_gst, 2)
            && $job->templateParams[6] === $paymentDueAt->format('M d, Y')
            && $job->templateParams[7] === $booking->payment_link
            && $job->templateParams[8] === $paymentDueAt->format('M d, Y');
    });
});
