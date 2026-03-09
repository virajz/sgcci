<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Exhibitors;

use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class InvitedGuests extends Component
{
    public Booking $booking;

    public ?int $deletingGuestId = null;

    public string $deletingGuestName = '';

    public function mount(Booking $booking): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $booking;
    }

    public function deleteGuest(): void
    {
        $guest = $this->booking->invitedGuests()->findOrFail($this->deletingGuestId);

        $guest->delete();

        $this->deletingGuestId = null;
        $this->deletingGuestName = '';

        Flux::toast(heading: 'Guest Removed', variant: 'success', text: 'The invited guest has been removed.');
    }

    public function render()
    {
        $guests = $this->booking->invitedGuests()
            ->where('exhibition_id', $this->booking->exhibition_id)
            ->latest()
            ->get();

        return view('livewire.admin.exhibitors.invited-guests', [
            'guests' => $guests,
            'guestCount' => $guests->count(),
        ]);
    }
}
