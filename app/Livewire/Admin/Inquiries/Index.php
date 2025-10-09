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

    protected $listeners = ['refresh-inquiries' => '$refresh'];

    public function releaseStall(int $bookingId): void
    {
        $booking = Booking::where('id', $bookingId)
            ->where('is_manual_block', true)
            ->first();

        if ($booking) {
            $booking->delete();
            session()->flash('success', 'Stall released successfully.');
        }
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with(['exhibition', 'adminApprovedBy', 'superAdminApprovedBy', 'rejectedBy', 'blockedBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('booking_code', 'like', "%{$this->search}%")
                        ->orWhere('brand_name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter === 'manual_block', function ($query) {
                // Only show manual blocks
                $query->where('is_manual_block', true);
            })
            ->when($this->statusFilter !== 'all' && $this->statusFilter !== 'manual_block', function ($query) {
                // Show only non-manual blocks with the specified status
                $query->where('status', $this->statusFilter)
                    ->where('is_manual_block', false);
            })
            ->when($this->statusFilter === 'all', function ($query) {
                // Show all bookings including manual blocks
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.inquiries.index', [
            'bookings' => $bookings,
        ]);
    }
}
