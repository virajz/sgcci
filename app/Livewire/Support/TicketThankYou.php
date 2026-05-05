<?php

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.front')]
class TicketThankYou extends Component
{
    public SupportTicket $ticket;

    public function mount(string $ticketNumber): void
    {
        $this->ticket = SupportTicket::with('booking.exhibition')
            ->where('ticket_number', $ticketNumber)
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.support.ticket-thank-you');
    }
}
