<?php

namespace App\Livewire\Admin\Inquiries;

use App\Jobs\SendSmsMessage;
use App\Models\Booking;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    /** @var array<int> */
    public array $selectedBookings = [];

    public bool $showBulkMoveModal = false;

    public bool $showMoveConfirmModal = false;

    public ?int $confirmMoveId = null;

    public string $confirmMoveName = '';

    /** @var array<string, bool> */
    public array $defaultColumns = [
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

        // Load column preferences from session to pass to Alpine as initial state
        $savedColumns = session('admin.inquiries.visible_columns');
        if ($savedColumns && is_array($savedColumns)) {
            $this->defaultColumns = array_merge($this->defaultColumns, $savedColumns);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedBookings = [];
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedBookings = [];
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

    public function openBulkMoveModal(): void
    {
        if (empty($this->selectedBookings)) {
            Flux::toast(heading: 'No Selection', variant: 'warning', text: 'Please select at least one booking.');

            return;
        }

        $this->showBulkMoveModal = true;
    }

    public function bulkMoveToExhibitor(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only super admins can move inquiries to exhibitors.');
            $this->showBulkMoveModal = false;

            return;
        }

        $bookings = Booking::whereIn('id', $this->selectedBookings)
            ->whereNull('login_password')
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $this->createExhibitorAccount($booking);
            $count++;
        }

        $this->selectedBookings = [];
        $this->showBulkMoveModal = false;

        Flux::toast(
            heading: 'Moved to Exhibitor!',
            variant: 'success',
            text: "{$count} booking(s) added to the exhibitors list with login credentials generated."
        );
    }

    protected function createExhibitorAccount(Booking $booking): void
    {
        $plainPassword = 'SGCCI@'.strtoupper(Str::random(6));

        $user = User::firstOrCreate(
            ['email' => $booking->email],
            [
                'name' => $booking->contact_person,
                'role' => 'exhibitor',
                'password' => Hash::make($plainPassword),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->update([
                'role' => 'exhibitor',
                'password' => Hash::make($plainPassword),
            ]);
        }

        $booking->update([
            'exhibitor_user_id' => $user->id,
            'login_password' => $plainPassword,
        ]);

        if (config('services.sms.enabled')) {
            SendSmsMessage::dispatch(
                template: 'exhibitor_credentials',
                phoneCode: $booking->phone_code,
                phoneNumber: $booking->phone_number,
                variables: [
                    'exhibition' => $booking->exhibition?->title ?? 'SGCCI Auto Expo',
                    'login_url' => route('login'),
                    'email' => $booking->email,
                    'password' => $plainPassword,
                ]
            );
        }
    }

    public function openMoveConfirmModal(int $bookingId, string $brandName): void
    {
        $this->confirmMoveId = $bookingId;
        $this->confirmMoveName = $brandName;
        $this->showMoveConfirmModal = true;
    }

    public function moveToExhibitor(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only super admins can move inquiries to exhibitors.');
            $this->showMoveConfirmModal = false;

            return;
        }

        $booking = Booking::findOrFail($this->confirmMoveId);

        if ($booking->login_password) {
            Flux::toast(heading: 'Already an Exhibitor', variant: 'warning', text: 'This booking already has exhibitor credentials.');
            $this->showMoveConfirmModal = false;

            return;
        }

        $this->createExhibitorAccount($booking);

        $this->showMoveConfirmModal = false;
        $this->confirmMoveId = null;
        $this->confirmMoveName = '';

        Flux::toast(heading: 'Moved to Exhibitor!', variant: 'success', text: "{$booking->brand_name} has been added to the exhibitors list with login credentials generated.");
    }

    public function resetColumns(): void
    {
        $defaults = [
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

        session(['admin.inquiries.visible_columns' => $defaults]);
        $this->dispatch('columns-reset', columns: $defaults);

        Flux::toast(
            heading: 'Columns Reset',
            variant: 'success',
            text: 'All columns are now visible.'
        );
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
            ->when($this->statusFilter === 'all', fn ($query) => $query)
            ->latest()
            ->paginate(15);

        return view('livewire.admin.inquiries.index', [
            'bookings' => $bookings,
        ]);
    }
}
