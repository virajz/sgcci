<?php

namespace App\Livewire\Admin\Inquiries;

use App\BookingStatus;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Booking $booking;

    public bool $showRejectModal = false;

    public string $rejectionReason = '';

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function approve(): void
    {
        $user = Auth::user();

        // Admin can only verify/approve for super admin review
        if (! $user->isSuperAdmin() && $this->booking->status === BookingStatus::PendingApproval) {
            $this->booking->update([
                'status' => BookingStatus::ApprovedByAdmin,
                'admin_approved_by' => $user->id,
                'admin_approved_at' => now(),
            ]);

            session()->flash('success', 'Booking verified successfully. Awaiting super admin approval for stall allotment.');
            $this->dispatch('booking-updated');

            return;
        }

        // Super admin can approve and send payment link
        if ($user->isSuperAdmin() && $this->booking->status === BookingStatus::ApprovedByAdmin) {
            $paymentDueAt = now()->addDays(3);

            $this->booking->update([
                'status' => BookingStatus::PaymentPending,
                'super_admin_approved_by' => $user->id,
                'super_admin_approved_at' => now(),
                'payment_link' => $this->generatePaymentLink(),
                'payment_link_sent_at' => now(),
                'payment_due_at' => $paymentDueAt,
            ]);

            // Send WhatsApp notification for booking confirmation with payment link
            SendWhatsAppCampaign::dispatch(
                campaignName: 'booking_confirmationpayment',
                phoneCode: $this->booking->phone_code,
                phoneNumber: $this->booking->phone_number,
                templateParams: [
                    $this->booking->contact_person,                          // {{1}} Contact Person Name
                    $this->booking->exhibition->title,                       // {{2}} Exhibition Title
                    implode(', ', $this->booking->selected_stalls),          // {{3}} Allotted Stalls
                    $this->booking->booking_code,                            // {{4}} Booking Code
                    number_format($this->booking->total_area, 0),            // {{5}} Total Area
                    number_format($this->booking->total_with_gst, 2),        // {{6}} Total Amount with GST
                    $paymentDueAt->format('M d, Y'),                         // {{7}} Payment Due Date (first)
                    $this->booking->payment_link,                            // {{8}} Payment Link URL
                    $paymentDueAt->format('M d, Y'),                         // {{9}} Payment Due Date (repeated)
                ]
            );

            session()->flash('success', 'Booking approved! Payment link sent to customer. Stalls will be allotted once payment is received.');
            $this->dispatch('booking-updated');

            return;
        }

        session()->flash('error', 'Unable to approve booking at this stage.');
    }

    public function openRejectModal(): void
    {
        $this->showRejectModal = true;
        $this->rejectionReason = '';
    }

    public function reject(): void
    {
        // Only super admin can reject bookings
        if (! Auth::user()->isSuperAdmin()) {
            session()->flash('error', 'Only super admin can reject bookings.');
            $this->showRejectModal = false;

            return;
        }

        $this->validate([
            'rejectionReason' => 'required|string|min:10',
        ]);

        $this->booking->update([
            'status' => BookingStatus::Rejected,
            'rejection_reason' => $this->rejectionReason,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
        ]);

        // Send WhatsApp notification for booking rejection
        SendWhatsAppCampaign::dispatch(
            campaignName: 'booking_reject',
            phoneCode: $this->booking->phone_code,
            phoneNumber: $this->booking->phone_number,
            templateParams: [
                $this->booking->contact_person,                          // {{1}} Contact Person Name
                $this->booking->exhibition->title,                       // {{2}} Exhibition Title
                $this->booking->booking_code,                            // {{3}} Booking Code
                implode(', ', $this->booking->selected_stalls),          // {{4}} Requested Stalls
                $this->rejectionReason,                                  // {{5}} Rejection Reason
            ]
        );

        $this->showRejectModal = false;
        session()->flash('success', 'Booking has been rejected.');
        $this->dispatch('booking-updated');
    }

    public function markPaymentCompleted(): void
    {
        // Only super admin can mark payment as completed
        if (! Auth::user()->isSuperAdmin()) {
            session()->flash('error', 'Only super admin can mark payment as completed.');

            return;
        }

        // Only payment pending bookings can be marked as completed
        if ($this->booking->status !== BookingStatus::PaymentPending) {
            session()->flash('error', 'Only bookings with payment pending status can be marked as completed.');

            return;
        }

        $this->booking->update([
            'status' => BookingStatus::Allotted,
            'payment_completed_at' => now(),
        ]);

        // Send WhatsApp notification for payment success
        SendWhatsAppCampaign::dispatch(
            campaignName: 'payment_success',
            phoneCode: $this->booking->phone_code,
            phoneNumber: $this->booking->phone_number,
            templateParams: [
                $this->booking->contact_person,                          // {{1}} Contact Person Name
                $this->booking->exhibition->title,                       // {{2}} Exhibition Title
                $this->booking->booking_code,                            // {{3}} Booking Code
                number_format($this->booking->total_with_gst, 2),        // {{4}} Amount Paid
                now()->format('M d, Y'),                                 // {{5}} Payment Date
                implode(', ', $this->booking->selected_stalls),          // {{6}} Confirmed Stalls
            ]
        );

        session()->flash('success', 'Payment marked as completed. Stalls have been allotted to the customer.');
        $this->dispatch('booking-updated');
    }

    protected function generatePaymentLink(): string
    {
        // In a real application, you would integrate with a payment gateway
        // For now, return a placeholder URL
        return config('app.url').'/payment/'.$this->booking->booking_code;
    }

    public function render()
    {
        return view('livewire.admin.inquiries.show');
    }
}
