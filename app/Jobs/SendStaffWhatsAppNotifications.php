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

        // Send WhatsApp notification to each active staff member
        foreach ($staffMembers as $staff) {
            SendWhatsAppCampaign::dispatch(
                campaignName: $this->campaignName,
                phoneCode: $staff->phone_code,
                phoneNumber: $staff->phone_number,
                templateParams: $templateParams
            );
        }
    }

    /**
     * Build template parameters for WhatsApp message
     */
    protected function buildTemplateParams(): array
    {
        return match ($this->campaignName) {
            'booking_received' => $this->buildBookingReceivedParams(),
            'booking_confirmationpayment' => $this->buildBookingConfirmationPaymentParams(),
            'payment_success' => $this->buildPaymentSuccessParams(),
            default => [],
        };
    }

    /**
     * Build params for booking_received campaign
     */
    protected function buildBookingReceivedParams(): array
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
     * Build params for booking_confirmationpayment campaign
     */
    protected function buildBookingConfirmationPaymentParams(): array
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
}
