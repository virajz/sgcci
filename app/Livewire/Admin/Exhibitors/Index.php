<?php

namespace App\Livewire\Admin\Exhibitors;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingBadgeLimitId = null;

    public int $editingBadgeLimit = 5;

    /** @var array<int> */
    public array $selectedBookings = [];

    public bool $showBulkConfirmModal = false;

    public function mount(): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedBookings = [];
    }

    public function startEditingBadgeLimit(int $bookingId, int $currentLimit): void
    {
        $this->editingBadgeLimitId = $bookingId;
        $this->editingBadgeLimit = $currentLimit;
    }

    public function saveBadgeLimit(): void
    {
        $this->validate([
            'editingBadgeLimit' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $booking = Booking::findOrFail($this->editingBadgeLimitId);
        $booking->update(['badge_limit' => $this->editingBadgeLimit]);

        $this->editingBadgeLimitId = null;

        $this->dispatch('badge-limit-saved');
    }

    public function cancelEditingBadgeLimit(): void
    {
        $this->editingBadgeLimitId = null;
    }

    public function generateCredentials(int $bookingId): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only admins can generate credentials.');

            return;
        }

        $booking = Booking::findOrFail($bookingId);

        if ($booking->login_password) {
            Flux::toast(heading: 'Already Generated', variant: 'warning', text: 'Login credentials already exist for this booking.');

            return;
        }

        $this->createExhibitorAccount($booking);

        Flux::toast(heading: 'Credentials Generated!', variant: 'success', text: "Login credentials created for {$booking->brand_name}.");
    }

    public function openBulkConfirmModal(): void
    {
        if (empty($this->selectedBookings)) {
            Flux::toast(heading: 'No Selection', variant: 'warning', text: 'Please select at least one exhibitor.');

            return;
        }

        $this->showBulkConfirmModal = true;
    }

    public function bulkGenerateCredentials(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only admins can generate credentials.');
            $this->showBulkConfirmModal = false;

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
        $this->showBulkConfirmModal = false;

        Flux::toast(
            heading: 'Credentials Generated!',
            variant: 'success',
            text: "Login credentials created for {$count} exhibitor(s)."
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
    }

    public function render()
    {
        $search = strtolower($this->search);
        $phoneSearch = str_replace(' ', '', $this->search);

        $bookings = Booking::query()
            ->where(function ($q) {
                // Full payment completed OR partial payment with credentials already generated
                $q->where('status', BookingStatus::PaymentCompleted)
                    ->orWhere(function ($q2) {
                        $q2->where('status', BookingStatus::PaymentPending)
                            ->where('amount_paid', '>', 0)
                            ->whereNotNull('login_password');
                    });
            })
            ->where('is_manual_block', false)
            ->with(['exhibition', 'exhibitorUser', 'badgeMembers'])
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
