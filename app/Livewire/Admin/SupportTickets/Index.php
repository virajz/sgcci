<?php

namespace App\Livewire\Admin\SupportTickets;

use App\Models\SupportTicket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    #[Computed]
    public function tickets()
    {
        return SupportTicket::query()
            ->with(['booking', 'reviewedBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('ticket_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('booking', function ($bookingQuery) {
                            $bookingQuery->where('booking_code', 'like', '%'.$this->search.'%')
                                ->orWhere('brand_name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(15);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.support-tickets.index');
    }
}
