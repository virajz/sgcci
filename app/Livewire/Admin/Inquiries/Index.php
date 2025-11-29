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

    public array $visibleColumns = [
        'booking_code' => true,
        'brand_name' => true,
        'contact_person' => true,
        'phone' => true,
        'membership' => true,
        'stalls' => true,
        'amount' => true,
        'part_payment' => true,
        'status' => true,
        'date' => true,
    ];

    public function mount(): void
    {
        // If tab parameter is provided in URL, use it
        $tab = request()->query('tab');
        if ($tab && in_array($tab, ['all', 'pending_approval', 'approved_by_admin', 'payment_pending', 'payment_completed', 'manual_block', 'rejected', 'expired'])) {
            $this->statusFilter = $tab;
        } elseif (! $tab && Auth::user()->isSuperAdmin()) {
            // Super admin default tab is 'approved_by_admin' (bookings awaiting their approval)
            $this->statusFilter = 'approved_by_admin';
        }

        // Load column preferences from session
        $savedColumns = session('admin.inquiries.visible_columns');
        if ($savedColumns && is_array($savedColumns)) {
            $this->visibleColumns = array_merge($this->visibleColumns, $savedColumns);
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

    public function updatedVisibleColumns(): void
    {
        $this->saveColumnPreferences();
    }

    public function resetColumns(): void
    {
        $this->visibleColumns = [
            'booking_code' => true,
            'brand_name' => true,
            'contact_person' => true,
            'phone' => true,
            'membership' => true,
            'stalls' => true,
            'amount' => true,
            'part_payment' => true,
            'status' => true,
            'date' => true,
        ];
        $this->saveColumnPreferences();

        Flux::toast(
            heading: 'Columns Reset',
            variant: 'success',
            text: 'All columns are now visible.'
        );
    }

    protected function saveColumnPreferences(): void
    {
        session(['admin.inquiries.visible_columns' => $this->visibleColumns]);
    }

    public function getColumnLabel(string $column): string
    {
        return match ($column) {
            'booking_code' => 'Booking Code',
            'brand_name' => 'Brand Name',
            'contact_person' => 'Contact Person',
            'phone' => 'Phone',
            'membership' => 'Membership',
            'stalls' => 'Stalls',
            'amount' => 'Amount',
            'part_payment' => 'Part Payment',
            'status' => 'Status',
            'date' => 'Date',
            default => ucfirst($column),
        };
    }

    public function render()
    {
        $search = strtolower($this->search);
        // Remove spaces for phone number search
        $phoneSearch = str_replace(' ', '', $this->search);

        $bookings = Booking::query()
            ->with(['exhibition', 'adminApprovedBy', 'superAdminApprovedBy', 'rejectedBy', 'blockedBy'])
            ->when($this->search, function ($query) use ($search, $phoneSearch) {
                $query->where(function ($q) use ($search, $phoneSearch) {
                    $q->whereRaw('LOWER(booking_code) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(brand_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(contact_person) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw("REPLACE(phone_number, ' ', '') LIKE ?", ["%{$phoneSearch}%"]);
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
