<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\StaffMember;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendStaffWhatsAppNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $campaignName = 'booking_confirmationpayment'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Only send if WhatsApp is enabled
        if (! config('services.whatsapp.enabled')) {
            return;
        }

        // Get all active staff members
        $staffMembers = StaffMember::active()->get();

        // Build template params based on booking
        $templateParams = $this->buildTemplateParams();

        // Determine the staff-specific campaign name
        $staffCampaignName = $this->getStaffCampaignName();

        // Send WhatsApp notification to each active staff member
        foreach ($staffMembers as $staff) {
            SendWhatsAppCampaign::dispatch(
                campaignName: $staffCampaignName,
                phoneCode: $staff->phone_code,
                phoneNumber: $staff->phone_number,
                templateParams: $templateParams
            );
        }
    }

    /**
     * Get staff-specific campaign name
     */
    protected function getStaffCampaignName(): string
    {
        return match ($this->campaignName) {
            'booking_received' => 'staff_booking_received',
            'booking_confirmationpayment' => 'staff_booking_confirmationpayment',
            'payment_success' => 'invoicestatus',
            default => $this->campaignName,
        };
    }

    /**
     * Build template parameters for WhatsApp message
     */
    protected function buildTemplateParams(): array
    {
        return match ($this->campaignName) {
            'booking_received' => $this->buildStaffBookingReceivedParams(),
            'booking_confirmationpayment' => $this->buildStaffBookingConfirmationPaymentParams(),
            'payment_success' => $this->buildStaffPaymentSuccessParams(),
            default => [],
        };
    }

    /**
     * Build params for staff_booking_received campaign
     */
    protected function buildStaffBookingReceivedParams(): array
    {
        return [
            $this->booking->contact_person,                              // {{1}} Contact Person Name
            $this->booking->exhibition->title,                           // {{2}} Exhibition Title
            implode(', ', $this->booking->selected_stalls),              // {{3}} Selected Stalls
            $this->booking->booking_code,                                // {{4}} Booking Code
            number_format($this->booking->total_area, 0),                // {{5}} Total Area
            number_format($this->booking->total_with_gst, 2),            // {{6}} Total Amount with GST
        ];
    }

    /**
     * Build params for staff_booking_confirmationpayment campaign
     */
    protected function buildStaffBookingConfirmationPaymentParams(): array
    {
        $paymentDueAt = $this->booking->payment_due_at ?? now()->addDays(7);

        return [
            $this->booking->contact_person,                              // {{1}} Contact Person Name
            $this->booking->exhibition->title,                           // {{2}} Exhibition Title
            implode(', ', $this->booking->selected_stalls),              // {{3}} Allotted Stalls
            $this->booking->booking_code,                                // {{4}} Booking Code
            number_format($this->booking->total_area, 0),                // {{5}} Total Area
            number_format($this->booking->total_with_gst, 2),            // {{6}} Total Amount with GST
            $paymentDueAt->format('M d, Y'),                             // {{7}} Payment Due Date (first)
            $this->booking->payment_link ?? $this->booking->getPaymentUrl(), // {{8}} Payment Link URL
            $paymentDueAt->format('M d, Y'),                             // {{9}} Payment Due Date (repeated)
        ];
    }

    /**
     * Build params for payment_success campaign
     */
    protected function buildPaymentSuccessParams(): array
    {
        return [
            $this->booking->contact_person,                              // {{1}} Contact Person Name
            $this->booking->exhibition->title,                           // {{2}} Exhibition Title
            $this->booking->booking_code,                                // {{3}} Booking Code
            number_format($this->booking->total_with_gst, 2),            // {{4}} Amount Paid
            $this->booking->payment_completed_at?->format('M d, Y') ?? now()->format('M d, Y'), // {{5}} Payment Date
            implode(', ', $this->booking->selected_stalls),              // {{6}} Confirmed Stalls
        ];
    }

    /**
     * Build params for staff_payment_success campaign
     */
    protected function buildStaffPaymentSuccessParams(): array
    {
        return [
            $this->booking->contact_person,                              // {{1}} Contact Person Name
            $this->booking->exhibition->title,                           // {{2}} Exhibition Title
            $this->booking->booking_code,                                // {{3}} Booking Code
            number_format($this->booking->total_with_gst, 2),            // {{4}} Amount Paid
            $this->booking->payment_completed_at?->format('M d, Y') ?? now()->format('M d, Y'), // {{5}} Payment Date
            implode(', ', $this->booking->selected_stalls),              // {{6}} Confirmed Stalls
        ];
    }
}
