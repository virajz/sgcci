<?php

namespace App\Livewire\Admin\Inquiries;

use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with(['exhibition', 'adminApprovedBy', 'superAdminApprovedBy', 'rejectedBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('booking_code', 'like', "%{$this->search}%")
                        ->orWhere('brand_name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.inquiries.index', [
            'bookings' => $bookings,
        ]);
    }
}
