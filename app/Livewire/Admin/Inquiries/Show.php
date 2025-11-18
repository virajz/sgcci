<?php

namespace App\Livewire\Admin\Inquiries;

use App\BookingStatus;
use App\Jobs\SendSmsMessage;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Booking $booking;

    public bool $showRejectModal = false;

    public string $rejectionReason = '';

    public bool $showRefundModal = false;

    public string $refundReason = '';

    public bool $showReleaseModal = false;

    public string $releaseReason = '';

    public function mount(Booking $booking): void
    {
        // Ensure user has admin privileges
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $booking;
    }

    #[On('booking-updated')]
    public function refreshBooking(): void
    {
        $this->booking->refresh();
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

            Flux::toast(
                heading: 'Booking Verified!',
                variant: 'success',
                text: 'Booking verified successfully. Awaiting super admin approval for stall allotment.'
            );
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
            if (config('services.whatsapp.enabled')) {
                SendWhatsAppCampaign::dispatch(
                    campaignName: 'booking_confirmationpayment',
                    phoneCode: $this->booking->phone_code,
                    phoneNumber: $this->booking->phone_number,
                    templateParams: [
                        (string) $this->booking->contact_person,                 // {{1}} Contact Person Name
                        (string) $this->booking->exhibition->title,              // {{2}} Exhibition Title
                        implode(', ', $this->booking->selected_stalls),          // {{3}} Allotted Stalls
                        (string) $this->booking->booking_code,                   // {{4}} Booking Code
                        (string) number_format($this->booking->total_area, 0),   // {{5}} Total Area
                        (string) number_format($this->booking->total_with_gst, 2), // {{6}} Total Amount with GST
                        $paymentDueAt->format('M d, Y'),                         // {{7}} Payment Due Date (first)
                        (string) $this->booking->payment_link,                   // {{8}} Payment Link URL
                        $paymentDueAt->format('M d, Y'),                         // {{9}} Payment Due Date (repeated)
                    ]
                );

                // Send WhatsApp notification to staff members
                \App\Jobs\SendStaffWhatsAppNotifications::dispatch(
                    booking: $this->booking,
                    campaignName: 'booking_confirmationpayment'
                );
            }

            // Send SMS notification for booking confirmation with payment link
            if (config('services.sms.enabled')) {
                SendSmsMessage::dispatch(
                    template: 'booking_confirmation_payment',
                    phoneCode: $this->booking->phone_code,
                    phoneNumber: $this->booking->phone_number,
                    variables: [
                        'contact_name' => explode(' ', trim($this->booking->contact_person))[0],
                        'exhibition' => $this->abbreviateTitle($this->booking->exhibition->title),
                        'booking_code' => $this->booking->booking_code,
                        'due_date' => $paymentDueAt->format('d/m'),
                        'payment_link' => $this->booking->payment_link,
                    ]
                );
            }

            Flux::toast(
                heading: 'Booking Approved!',
                variant: 'success',
                text: 'Payment link sent to customer. Stalls will be allotted once payment is received.'
            );
            $this->dispatch('booking-updated');

            return;
        }

        Flux::toast(
            heading: 'Unable to Proceed',
            variant: 'danger',
            text: 'Unable to approve booking at this stage. Please check the booking status.'
        );
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
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only super admin can reject bookings.'
            );
            $this->showRejectModal = false;

            return;
        }

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:10'],
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
            'rejectionReason.min' => 'Please provide a detailed reason (minimum 10 characters).',
        ]);

        $this->booking->update([
            'status' => BookingStatus::Rejected,
            'rejection_reason' => $this->rejectionReason,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
        ]);

        // Send WhatsApp notification for booking rejection
        if (config('services.whatsapp.enabled')) {
            // Generate support ticket link with booking code pre-filled
            $supportTicketUrl = route('support-tickets.create', ['ticket' => $this->booking->booking_code]);

            SendWhatsAppCampaign::dispatch(
                campaignName: 'bookingrejected',
                phoneCode: $this->booking->phone_code,
                phoneNumber: $this->booking->phone_number,
                templateParams: [
                    $this->booking->contact_person,                          // {{1}} Contact Person Name
                    $this->booking->exhibition->title,                       // {{2}} Exhibition Title
                    $this->booking->booking_code,                            // {{3}} Booking Code
                    implode(', ', $this->booking->selected_stalls),          // {{4}} Requested Stalls
                    $this->rejectionReason,                                  // {{5}} Rejection Reason
                    $supportTicketUrl,                                       // {{6}} Support Ticket Link
                ]
            );
        }

        // Send SMS notification for booking rejection
        if (config('services.sms.enabled')) {
            // Truncate rejection reason to fit within SMS character limit
            $shortReason = strlen($this->rejectionReason) > 30
                ? substr($this->rejectionReason, 0, 27).'...'
                : $this->rejectionReason;

            SendSmsMessage::dispatch(
                template: 'booking_rejected',
                phoneCode: $this->booking->phone_code,
                phoneNumber: $this->booking->phone_number,
                variables: [
                    'contact_name' => explode(' ', trim($this->booking->contact_person))[0],
                    'booking_code' => $this->booking->booking_code,
                    'exhibition' => $this->abbreviateTitle($this->booking->exhibition->title),
                    'reason' => $shortReason,
                ]
            );
        }

        $this->showRejectModal = false;
        Flux::toast(
            heading: 'Booking Rejected',
            variant: 'success',
            text: 'The booking has been rejected and the customer has been notified.'
        );
        $this->dispatch('booking-updated');
    }

    public function resendPaymentLink(): void
    {

        // Only payment pending bookings can have payment link resent
        if ($this->booking->status !== BookingStatus::PaymentPending) {
            Flux::toast(
                heading: 'Cannot Resend Link',
                variant: 'danger',
                text: 'Payment link can only be resent for bookings with payment pending status.'
            );

            return;
        }

        $paymentDueAt = $this->booking->payment_due_at ?? now()->addDays(3);

        // Update the payment link sent timestamp
        $this->booking->update([
            'payment_link_sent_at' => now(),
        ]);

        // Send WhatsApp notification for booking confirmation with payment link
        if (config('services.whatsapp.enabled')) {
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

            // Send WhatsApp notification to staff members
            \App\Jobs\SendStaffWhatsAppNotifications::dispatch(
                booking: $this->booking,
                campaignName: 'booking_confirmationpayment'
            );
        }

        // Send SMS notification for booking confirmation with payment link
        if (config('services.sms.enabled')) {
            SendSmsMessage::dispatch(
                template: 'booking_confirmation_payment',
                phoneCode: $this->booking->phone_code,
                phoneNumber: $this->booking->phone_number,
                variables: [
                    'contact_name' => explode(' ', trim($this->booking->contact_person))[0],
                    'exhibition' => $this->abbreviateTitle($this->booking->exhibition->title),
                    'booking_code' => $this->booking->booking_code,
                    'due_date' => $paymentDueAt->format('d/m'),
                    'payment_link' => $this->booking->payment_link,
                ]
            );
        }

        Flux::toast(
            heading: 'Payment Link Sent!',
            variant: 'success',
            text: 'Payment link has been resent to the customer via WhatsApp and SMS.'
        );
        $this->dispatch('booking-updated');
    }

    public function markPaymentCompleted(): void
    {
        // Only super admin can mark payment as completed
        if (! Auth::user()->isSuperAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only super admin can mark payment as completed.'
            );

            return;
        }

        // Only payment pending bookings can be marked as completed
        if ($this->booking->status !== BookingStatus::PaymentPending) {
            Flux::toast(
                heading: 'Invalid Status',
                variant: 'danger',
                text: 'Only bookings with payment pending status can be marked as completed.'
            );

            return;
        }

        $this->booking->update([
            'status' => BookingStatus::Allotted,
            'payment_completed_at' => now(),
        ]);

        // Send WhatsApp notification for payment success
        if (config('services.whatsapp.enabled')) {
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
        }

        Flux::toast(
            heading: 'Payment Confirmed!',
            variant: 'success',
            text: 'Payment marked as completed. Stalls have been allotted to the customer.'
        );
        $this->dispatch('booking-updated');
    }

    public function reEnableExpiredBooking(): void
    {
        // Only admin or super admin can re-enable bookings
        if (! Auth::user()->isAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only admin can re-enable bookings.'
            );

            return;
        }

        // Only expired, cancelled, or rejected bookings can be re-enabled
        if (! in_array($this->booking->status, [BookingStatus::Expired, BookingStatus::Cancelled, BookingStatus::Rejected])) {
            Flux::toast(
                heading: 'Invalid Status',
                variant: 'danger',
                text: 'Only expired, cancelled, or rejected bookings can be re-enabled.'
            );

            return;
        }

        // Extend payment due date by 3 more days
        $newPaymentDueAt = now()->addDays(3);

        $this->booking->update([
            'status' => BookingStatus::PaymentPending,
            'payment_due_at' => $newPaymentDueAt,
            'payment_link_sent_at' => now(),
        ]);

        // Send WhatsApp notification with extended payment link
        if (config('services.whatsapp.enabled')) {
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
                    $newPaymentDueAt->format('M d, Y'),                      // {{7}} Payment Due Date (first)
                    $this->booking->payment_link,                            // {{8}} Payment Link URL
                    $newPaymentDueAt->format('M d, Y'),                      // {{9}} Payment Due Date (repeated)
                ]
            );

            // Send WhatsApp notification to staff members
            \App\Jobs\SendStaffWhatsAppNotifications::dispatch(
                booking: $this->booking,
                campaignName: 'booking_confirmationpayment'
            );
        }

        Flux::toast(
            heading: 'Booking Re-enabled!',
            variant: 'success',
            text: 'The booking has been re-enabled and payment link sent to customer.'
        );
        $this->dispatch('booking-updated');
    }

    public function openRefundModal(): void
    {
        $this->showRefundModal = true;
        $this->refundReason = '';
    }

    public function refundAndRelease(): void
    {
        // Only super admin can refund bookings
        if (! Auth::user()->isSuperAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only super admin can refund bookings.'
            );
            $this->showRefundModal = false;

            return;
        }

        // Validate that booking has payment
        if ($this->booking->amount_paid <= 0) {
            Flux::toast(
                heading: 'No Payment to Refund',
                variant: 'danger',
                text: 'This booking has no payment to refund.'
            );
            $this->showRefundModal = false;

            return;
        }

        // Validate refund reason
        $this->validate([
            'refundReason' => ['required', 'string', 'min:10'],
        ], [
            'refundReason.required' => 'Please provide a reason for refund.',
            'refundReason.min' => 'Please provide a detailed reason (minimum 10 characters).',
        ]);

        // Update booking status to refunded and release the stalls
        $this->booking->update([
            'status' => BookingStatus::Refunded,
            'refund_reason' => $this->refundReason,
            'refunded_by' => Auth::id(),
            'refunded_at' => now(),
        ]);

        $this->showRefundModal = false;
        Flux::toast(
            heading: 'Booking Refunded & Stalls Released',
            variant: 'success',
            text: 'The booking has been refunded and the stalls are now available for other bookings.'
        );
        $this->dispatch('booking-updated');
    }

    public function openReleaseModal(): void
    {
        $this->showReleaseModal = true;
        $this->releaseReason = '';
    }

    public function releaseStalls(): void
    {
        // Any admin can release stalls
        if (! Auth::user()->isAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only admin can release stalls.'
            );
            $this->showReleaseModal = false;

            return;
        }

        // Validate release reason
        $this->validate([
            'releaseReason' => ['required', 'string', 'min:10'],
        ], [
            'releaseReason.required' => 'Please provide a reason for releasing stalls.',
            'releaseReason.min' => 'Please provide a detailed reason (minimum 10 characters).',
        ]);

        // Update booking status to cancelled and release the stalls
        $this->booking->update([
            'status' => BookingStatus::Cancelled,
            'rejection_reason' => $this->releaseReason,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
        ]);

        // Send WhatsApp notification for booking cancellation/release
        if (config('services.whatsapp.enabled')) {
            // Generate support ticket link with booking code pre-filled
            $supportTicketUrl = route('support-tickets.create', ['ticket' => $this->booking->booking_code]);

            SendWhatsAppCampaign::dispatch(
                campaignName: 'bookingrejected',
                phoneCode: $this->booking->phone_code,
                phoneNumber: $this->booking->phone_number,
                templateParams: [
                    $this->booking->contact_person,                          // {{1}} Contact Person Name
                    $this->booking->exhibition->title,                       // {{2}} Exhibition Title
                    $this->booking->booking_code,                            // {{3}} Booking Code
                    implode(', ', $this->booking->selected_stalls),          // {{4}} Requested Stalls
                    $this->releaseReason,                                    // {{5}} Release/Rejection Reason
                    $supportTicketUrl,                                       // {{6}} Support Ticket Link
                ]
            );
        }

        $this->showReleaseModal = false;
        Flux::toast(
            heading: 'Stalls Released',
            variant: 'success',
            text: 'The stalls have been released and the customer has been notified.'
        );
        $this->dispatch('booking-updated');
    }

    protected function generatePaymentLink(): string
    {
        // In a real application, you would integrate with a payment gateway
        // For now, return a placeholder URL
        return config('app.url').'/payment/'.$this->booking->booking_code;
    }

    /**
     * Abbreviate exhibition title for SMS.
     */
    private function abbreviateTitle(string $title, int $maxLength = 20): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        return substr($title, 0, $maxLength - 3).'...';
    }

    public function render()
    {
        return view('livewire.admin.inquiries.show');
    }
}
