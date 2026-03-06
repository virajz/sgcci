<?php

namespace App\Livewire\Admin\Exhibitors;

use App\BookingStatus;
use App\Jobs\SendSmsMessage;
use App\Models\Booking;
use App\Models\Exhibition;
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

    public bool $showBulkSmsConfirmModal = false;

    public bool $showGenerateConfirmModal = false;

    public ?int $confirmGenerateId = null;

    public string $confirmGenerateName = '';

    public bool $showDeleteExhibitorModal = false;

    public ?int $deleteExhibitorId = null;

    public string $deleteExhibitorName = '';

    public bool $showAddExhibitorModal = false;

    /** @var array<string, bool> */
    public array $visibleColumns = [
        'booking_code' => true,
        'brand_contact' => true,
        'email' => true,
        'phone' => true,
        'exhibition' => true,
        'stalls' => true,
        'badges' => true,
        'login_password' => true,
        'paid_at' => false,
    ];

    public string $addExhibitorBrandName = '';

    public string $addExhibitorContactPerson = '';

    public string $addExhibitorEmail = '';

    public string $addExhibitorPhoneCode = '+91';

    public string $addExhibitorPhoneNumber = '';

    public ?int $addExhibitorExhibitionId = null;

    public string $addExhibitorStalls = '';

    public function mount(): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $savedColumns = session('admin.exhibitors.visible_columns');
        if ($savedColumns && is_array($savedColumns)) {
            $this->visibleColumns = array_merge($this->visibleColumns, $savedColumns);
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

    public function openGenerateConfirmModal(int $bookingId, string $brandName): void
    {
        $this->confirmGenerateId = $bookingId;
        $this->confirmGenerateName = $brandName;
        $this->showGenerateConfirmModal = true;
    }

    public function generateCredentials(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only admins can generate credentials.');
            $this->showGenerateConfirmModal = false;

            return;
        }

        $booking = Booking::findOrFail($this->confirmGenerateId);

        if ($booking->login_password) {
            Flux::toast(heading: 'Already Generated', variant: 'warning', text: 'Login credentials already exist for this booking.');
            $this->showGenerateConfirmModal = false;

            return;
        }

        $this->createExhibitorAccount($booking);

        $this->showGenerateConfirmModal = false;
        $this->confirmGenerateId = null;
        $this->confirmGenerateName = '';

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

    public function sendCredentialsSms(int $bookingId): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only admins can send SMS.');

            return;
        }

        $booking = Booking::with('exhibition')->findOrFail($bookingId);

        if (! $booking->login_password) {
            Flux::toast(heading: 'No Credentials', variant: 'warning', text: 'No login credentials found for this booking.');

            return;
        }

        $this->dispatchCredentialsSms($booking);

        Flux::toast(heading: 'SMS Sent!', variant: 'success', text: "Login credentials SMS sent to {$booking->brand_name}.");
    }

    public function openBulkSmsConfirmModal(): void
    {
        if (empty($this->selectedBookings)) {
            Flux::toast(heading: 'No Selection', variant: 'warning', text: 'Please select at least one exhibitor.');

            return;
        }

        $this->showBulkSmsConfirmModal = true;
    }

    public function bulkSendCredentialsSms(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only admins can send SMS.');
            $this->showBulkSmsConfirmModal = false;

            return;
        }

        $bookings = Booking::whereIn('id', $this->selectedBookings)
            ->whereNotNull('login_password')
            ->with('exhibition')
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $this->dispatchCredentialsSms($booking);
            $count++;
        }

        $this->selectedBookings = [];
        $this->showBulkSmsConfirmModal = false;

        Flux::toast(
            heading: 'SMS Sent!',
            variant: 'success',
            text: "Login credentials SMS sent to {$count} exhibitor(s)."
        );
    }

    public function confirmDeleteExhibitor(int $bookingId, string $brandName): void
    {
        $this->deleteExhibitorId = $bookingId;
        $this->deleteExhibitorName = $brandName;
        $this->showDeleteExhibitorModal = true;
    }

    public function deleteExhibitor(): void
    {
        $booking = Booking::findOrFail($this->deleteExhibitorId);

        if (! $booking->is_manually_added) {
            Flux::toast(heading: 'Not Allowed', variant: 'danger', text: 'Only manually added exhibitors can be deleted.');
            $this->showDeleteExhibitorModal = false;

            return;
        }

        $userId = $booking->exhibitor_user_id;
        $booking->delete();

        if ($userId) {
            User::destroy($userId);
        }

        $this->showDeleteExhibitorModal = false;
        $this->deleteExhibitorId = null;
        $this->deleteExhibitorName = '';

        Flux::toast(heading: 'Deleted', variant: 'success', text: 'Exhibitor has been deleted.');
    }

    public function openAddExhibitorModal(): void
    {
        $this->addExhibitorBrandName = '';
        $this->addExhibitorContactPerson = '';
        $this->addExhibitorEmail = '';
        $this->addExhibitorPhoneCode = '+91';
        $this->addExhibitorPhoneNumber = '';
        $this->addExhibitorStalls = '';
        $this->addExhibitorExhibitionId = Exhibition::latest()->value('id');
        $this->showAddExhibitorModal = true;
    }

    public function addExhibitor(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(heading: 'Unauthorized', variant: 'danger', text: 'Only admins can add exhibitors.');

            return;
        }

        $this->validate([
            'addExhibitorBrandName' => ['required', 'string', 'max:255'],
            'addExhibitorContactPerson' => ['required', 'string', 'max:255'],
            'addExhibitorEmail' => ['required', 'email', 'max:255'],
            'addExhibitorPhoneCode' => ['required', 'string', 'max:10'],
            'addExhibitorPhoneNumber' => ['required', 'string', 'max:20'],
            'addExhibitorExhibitionId' => ['required', 'integer', 'exists:exhibitions,id'],
            'addExhibitorStalls' => ['nullable', 'string'],
        ]);

        $stalls = array_values(array_filter(array_map('trim', explode(',', $this->addExhibitorStalls))));

        $booking = Booking::create([
            'exhibition_id' => $this->addExhibitorExhibitionId,
            'brand_name' => $this->addExhibitorBrandName,
            'facia_name' => $this->addExhibitorBrandName,
            'contact_person' => $this->addExhibitorContactPerson,
            'email' => $this->addExhibitorEmail,
            'phone_code' => $this->addExhibitorPhoneCode,
            'phone_number' => $this->addExhibitorPhoneNumber,
            'city' => '',
            'product_profile' => [],
            'participation_years' => [],
            'selected_stalls' => $stalls,
            'space_type' => 'standard',
            'status' => BookingStatus::PaymentCompleted,
            'payment_completed_at' => now(),
            'is_manual_block' => false,
            'is_manually_added' => true,
            'has_exhibited_before' => false,
            'is_sgcci_member' => false,
        ]);

        $this->createExhibitorAccount($booking);

        $this->showAddExhibitorModal = false;

        Flux::toast(
            heading: 'Exhibitor Added!',
            variant: 'success',
            text: "Exhibitor {$booking->brand_name} has been added and credentials generated."
        );
    }

    protected function dispatchCredentialsSms(Booking $booking): void
    {
        if (! config('services.sms.enabled')) {
            return;
        }

        SendSmsMessage::dispatch(
            template: 'exhibitor_credentials',
            phoneCode: $booking->phone_code,
            phoneNumber: $booking->phone_number,
            variables: [
                'exhibition' => $booking->exhibition?->title ?? 'SGCCI Auto Expo',
                'login_url' => route('login'),
                'email' => $booking->email,
                'password' => $booking->login_password,
            ]
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

        $booking->refresh();
        $this->dispatchCredentialsSms($booking);
    }

    public function updatedVisibleColumns(): void
    {
        session(['admin.exhibitors.visible_columns' => $this->visibleColumns]);
    }

    public function resetColumns(): void
    {
        $this->visibleColumns = [
            'booking_code' => true,
            'brand_contact' => true,
            'email' => true,
            'phone' => true,
            'exhibition' => true,
            'stalls' => true,
            'badges' => true,
            'login_password' => true,
            'paid_at' => false,
        ];
        session(['admin.exhibitors.visible_columns' => $this->visibleColumns]);

        Flux::toast(heading: 'Columns Reset', variant: 'success', text: 'Columns reset to default.');
    }

    public function getColumnLabel(string $column): string
    {
        return match ($column) {
            'booking_code' => 'Booking Code',
            'brand_contact' => 'Brand / Contact',
            'email' => 'Email',
            'phone' => 'Phone',
            'exhibition' => 'Exhibition',
            'stalls' => 'Stalls',
            'badges' => 'Badges',
            'login_password' => 'Login Password',
            'paid_at' => 'Paid At',
            default => ucfirst($column),
        };
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
            'exhibitions' => Exhibition::orderByDesc('id')->get(),
        ]);
    }
}
