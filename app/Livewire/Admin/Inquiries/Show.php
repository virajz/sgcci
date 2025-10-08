<?php

namespace App\Livewire\Admin\Inquiries;

use App\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Super admin can allot stalls (final approval)
        if ($user->isSuperAdmin() && $this->booking->status === BookingStatus::ApprovedByAdmin) {
            $paymentDueAt = now()->addDays(3);

            $this->booking->update([
                'status' => BookingStatus::Allotted,
                'super_admin_approved_by' => $user->id,
                'super_admin_approved_at' => now(),
                'payment_link' => $this->generatePaymentLink(),
                'payment_link_sent_at' => now(),
                'payment_due_at' => $paymentDueAt,
            ]);

            // Log WhatsApp message for payment request
            Log::channel('whatsapp')->info('WhatsApp payment request to be sent', [
                'booking_code' => $this->booking->booking_code,
                'recipient' => $this->booking->phone_code.$this->booking->phone_number,
                'contact_person' => $this->booking->contact_person,
                'brand_name' => $this->booking->brand_name,
                'exhibition' => $this->booking->exhibition->title,
                'selected_stalls' => $this->booking->selected_stalls,
                'total_amount' => $this->booking->total_with_gst,
                'payment_link' => $this->booking->payment_link,
                'payment_due_date' => $paymentDueAt->format('M d, Y'),
                'template' => 'payment_request',
            ]);

            session()->flash('success', 'Stalls allotted successfully! Payment link sent to customer.');
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

        $this->showRejectModal = false;
        session()->flash('success', 'Booking has been rejected.');
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
