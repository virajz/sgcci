<?php

namespace App\Livewire\Exhibitions;

use App\Models\Booking;
use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.front')]
class ThankYou extends Component
{
    public Booking $booking;

    public Exhibition $exhibition;

    public function mount(Exhibition $exhibition, string $bookingCode): void
    {
        $this->exhibition = $exhibition;
        $this->booking = Booking::where('booking_code', $bookingCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.exhibitions.thank-you');
    }
}
