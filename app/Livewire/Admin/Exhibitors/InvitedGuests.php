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

    public bool $showDeleteModal = false;

    public ?int $deletingGuestId = null;

    public string $deletingGuestName = '';

    public string $search = '';

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
        $this->showDeleteModal = false;

        Flux::toast(heading: 'Guest Removed', variant: 'success', text: 'The invited guest has been removed.');
    }

    public function render()
    {
        $guests = $this->booking->invitedGuests()
            ->where('exhibition_id', $this->booking->exhibition_id)
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereRaw('LOWER(registration_code) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest()
            ->get();

        return view('livewire.admin.exhibitors.invited-guests', [
            'guests' => $guests,
            'guestCount' => $this->booking->invitedGuests()->where('exhibition_id', $this->booking->exhibition_id)->count(),
        ]);
    }
}
