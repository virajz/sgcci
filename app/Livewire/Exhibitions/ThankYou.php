<?php

namespace App\Livewire\Exhibitions;

use App\Models\Booking;
use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.front')]
class ThankYou extends Component
{
    #[Locked]
    public string $bookingCode;

    #[Locked]
    public int $exhibitionId;

    public function mount(Exhibition $exhibition, string $bookingCode): void
    {
        $this->exhibitionId = $exhibition->id;
        $this->bookingCode = $bookingCode;
    }

    public function render()
    {
        $exhibition = Exhibition::findOrFail($this->exhibitionId);
        $booking = Booking::where('booking_code', $this->bookingCode)
            ->where('exhibition_id', $this->exhibitionId)
            ->firstOrFail();

        return view('livewire.exhibitions.thank-you', [
            'exhibition' => $exhibition,
            'booking' => $booking,
        ]);
    }
}
