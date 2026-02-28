<?php

namespace App\Livewire\Admin\Exhibitors;

use App\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = strtolower($this->search);
        $phoneSearch = str_replace(' ', '', $this->search);

        $bookings = Booking::query()
            ->where('status', BookingStatus::PaymentCompleted)
            ->where('is_manual_block', false)
            ->with(['exhibition', 'exhibitorUser'])
            ->when($this->search, function ($query) use ($search, $phoneSearch) {
                $query->where(function ($q) use ($search, $phoneSearch) {
                    $q->whereRaw('LOWER(booking_code) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(brand_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(contact_person) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw("REPLACE(phone_number, ' ', '') LIKE ?", ["%{$phoneSearch}%"]);
                });
            })
            ->latest('payment_completed_at')
            ->paginate(20);

        return view('livewire.admin.exhibitors.index', [
            'bookings' => $bookings,
        ]);
    }
}
