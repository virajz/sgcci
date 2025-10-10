<?php

namespace App\Livewire\Admin\Inquiries;

use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(as: 'tab')]
    public string $statusFilter = 'all';

    public bool $showConfirmReleaseModal = false;

    public ?int $stallToRelease = null;

    public function mount(): void
    {
        // If tab parameter is provided in URL, use it
        $tab = request()->query('tab');
        if ($tab && in_array($tab, ['all', 'pending_approval', 'approved_by_admin', 'payment_pending', 'payment_completed', 'manual_block', 'rejected'])) {
            $this->statusFilter = $tab;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected $listeners = ['refresh-inquiries' => '$refresh'];

    public function confirmRelease(int $bookingId): void
    {
        $this->stallToRelease = $bookingId;
        $this->showConfirmReleaseModal = true;
    }

    public function releaseStall(): void
    {
        // Security check
        if (! Auth::user()->isAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only admins can release stalls.'
            );
            $this->showConfirmReleaseModal = false;
            $this->stallToRelease = null;

            return;
        }

        if (! $this->stallToRelease) {
            return;
        }

        $booking = Booking::where('id', $this->stallToRelease)
            ->where('is_manual_block', true)
            ->first();

        if ($booking) {
            $stallNumbers = implode(', ', $booking->selected_stalls);
            $booking->delete();

            Flux::toast(
                heading: 'Stalls Released!',
                variant: 'success',
                text: "Successfully released stalls: {$stallNumbers}"
            );
        }

        $this->showConfirmReleaseModal = false;
        $this->stallToRelease = null;
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
