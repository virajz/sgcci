<?php

namespace App\Livewire\Admin\SupportTickets;

use App\BookingStatus;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public SupportTicket $ticket;

    public bool $showApprovalModal = false;

    public bool $showRejectionModal = false;

    #[Validate('required|string|max:500')]
    public string $rejectionReason = '';

    public function mount(SupportTicket $ticket): void
    {
        $this->ticket = $ticket->load(['booking', 'reviewedBy']);
    }

    public function approve(): void
    {
        if ($this->ticket->status !== 'pending') {
            $this->addError('ticket', 'This ticket has already been reviewed.');

            return;
        }

        $this->ticket->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Update booking status to allow payment
        if (
            $this->ticket->booking->status === BookingStatus::Rejected ||
            $this->ticket->booking->status === BookingStatus::Cancelled ||
            $this->ticket->booking->status === BookingStatus::Refunded
        ) {
            $this->ticket->booking->update([
                'status' => BookingStatus::PaymentPending,
            ]);
        }

        $this->showApprovalModal = false;

        session()->flash('message', 'Ticket approved successfully! Booking can now proceed with payment.');

        $this->redirect(route('admin.support-tickets.index'));
    }

    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|max:500',
        ]);

        if ($this->ticket->status !== 'pending') {
            $this->addError('ticket', 'This ticket has already been reviewed.');

            return;
        }

        $this->ticket->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->showRejectionModal = false;

        session()->flash('message', 'Ticket rejected successfully.');

        $this->redirect(route('admin.support-tickets.index'));
    }

    public function render()
    {
        return view('livewire.admin.support-tickets.show');
    }
}
