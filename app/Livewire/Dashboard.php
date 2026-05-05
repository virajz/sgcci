<?php

namespace App\Livewire;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\ExhibitorLead;
use App\Models\WhatsAppInquiry;
use App\VisitorRegistrationStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    // Total stalls available in the exhibition (based on the map)
    private const TOTAL_STALLS = 104;

    #[Url(as: 'exhibition', except: '')]
    public string $exhibitionId = '';

    public function mount(): void
    {
        if (auth()->user()?->isFrontDesk()) {
            $this->redirect(route('front-desk.index'), navigate: true);
        }

        if (auth()->user()?->isSecurityDesk()) {
            $this->redirect(route('security-desk.index'), navigate: true);
        }
    }

    /**
     * @return Collection<int, Exhibition>
     */
    #[Computed]
    public function exhibitions(): Collection
    {
        return Exhibition::query()->orderBy('start_date', 'desc')->get(['id', 'title']);
    }

    private function selectedExhibitionId(): ?int
    {
        return $this->exhibitionId !== '' ? (int) $this->exhibitionId : null;
    }

    public function getAvailableStallsProperty(): int
    {
        $exhibitionId = $this->selectedExhibitionId() ?? Exhibition::query()->value('id');

        if (! $exhibitionId) {
            return self::TOTAL_STALLS;
        }

        // Get all stalls that are currently booked (not rejected, cancelled, or expired)
        $bookedStallsCount = Booking::where('exhibition_id', $exhibitionId)
            ->whereNotIn('status', [
                BookingStatus::Rejected->value,
                BookingStatus::Cancelled->value,
                BookingStatus::Expired->value,
            ])
            ->where('is_manual_block', false)
            ->get()
            ->flatMap(fn ($booking) => $booking->selected_stalls)
            ->unique()
            ->count();

        // Also count manual blocks
        $manualBlockCount = Booking::where('exhibition_id', $exhibitionId)
            ->where('is_manual_block', true)
            ->get()
            ->flatMap(fn ($booking) => $booking->selected_stalls)
            ->unique()
            ->count();

        return self::TOTAL_STALLS - $bookedStallsCount - $manualBlockCount;
    }

    public function getPaymentsDueProperty(): int
    {
        $threeDaysFromNow = now()->addDays(3);

        return Booking::query()
            ->when($this->selectedExhibitionId(), fn ($q, $id) => $q->where('exhibition_id', $id))
            ->where('status', BookingStatus::PaymentPending)
            ->where('is_manual_block', false)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', $threeDaysFromNow)
            ->where('payment_due_at', '>=', now())
            ->count();
    }

    public function getBookedStallsProperty(): int
    {
        $exhibitionId = $this->selectedExhibitionId() ?? Exhibition::query()->value('id');

        if (! $exhibitionId) {
            return 0;
        }

        return Booking::where('exhibition_id', $exhibitionId)
            ->where('status', BookingStatus::PaymentCompleted)
            ->where('is_manual_block', false)
            ->count();
    }

    public function getPendingReviewsProperty(): int
    {
        return Booking::query()
            ->when($this->selectedExhibitionId(), fn ($q, $id) => $q->where('exhibition_id', $id))
            ->where('status', BookingStatus::PendingApproval)
            ->where('is_manual_block', false)
            ->count();
    }

    public function getVisitorsTodayProperty(): int
    {
        return ExhibitionVisitor::query()
            ->when($this->selectedExhibitionId(), fn ($q, $id) => $q->where('exhibition_id', $id))
            ->where('status', VisitorRegistrationStatus::Confirmed)
            ->whereDate('created_at', today())
            ->count();
    }

    public function getVisitorsTotalProperty(): int
    {
        return ExhibitionVisitor::query()
            ->when($this->selectedExhibitionId(), fn ($q, $id) => $q->where('exhibition_id', $id))
            ->where('status', VisitorRegistrationStatus::Confirmed)
            ->count();
    }

    public function getVisitorPaymentTodayProperty(): string
    {
        $amount = ExhibitionVisitor::query()
            ->when($this->selectedExhibitionId(), fn ($q, $id) => $q->where('exhibition_id', $id))
            ->where('status', VisitorRegistrationStatus::Confirmed)
            ->whereDate('created_at', today())
            ->sum('payment_amount');

        return '₹'.number_format($amount, 0);
    }

    public function getVisitorPaymentTotalProperty(): string
    {
        $amount = ExhibitionVisitor::query()
            ->when($this->selectedExhibitionId(), fn ($q, $id) => $q->where('exhibition_id', $id))
            ->where('status', VisitorRegistrationStatus::Confirmed)
            ->sum('payment_amount');

        return '₹'.number_format($amount, 0);
    }

    /** @return array{total_entered: int, today: int, inside: int, daily: array<string, int>} */
    public function getScanStatsProperty(): array
    {
        $exhibitionId = $this->selectedExhibitionId();

        $base = fn () => ExhibitionVisitor::query()
            ->when($exhibitionId, fn ($q, $id) => $q->where('exhibition_id', $id));

        $totalEntered = $base()->whereNotNull('entered_at')->count();
        $totalExited = $base()->whereNotNull('exited_at')->count();
        $today = $base()->whereNotNull('entered_at')->whereDate('entered_at', today())->count();

        $rows = $base()
            ->whereNotNull('entered_at')
            ->where('entered_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(entered_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();

        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $daily[] = ['date' => $day, 'entries' => $rows[$day] ?? 0];
        }

        return [
            'total_entered' => $totalEntered,
            'today' => $today,
            'inside' => $totalEntered - $totalExited,
            'daily' => $daily,
        ];
    }

    public function render()
    {
        $extraData = [];

        if (auth()->user()?->isExhibitor()) {
            $booking = auth()->user()->booking;
            $extraData['leadsCount'] = $booking
                ? ExhibitorLead::where('booking_id', $booking->id)->count()
                : 0;
            $extraData['whatsAppInquiriesCount'] = $booking
                ? WhatsAppInquiry::where('booking_id', $booking->id)->count()
                : 0;
        }

        return view('livewire.dashboard', array_merge([
            'availableStalls' => $this->availableStalls,
            'paymentsDue' => $this->paymentsDue,
            'bookedStalls' => $this->bookedStalls,
            'pendingReviews' => $this->pendingReviews,
            'visitorsToday' => $this->visitorsToday,
            'visitorsTotal' => $this->visitorsTotal,
            'visitorPaymentToday' => $this->visitorPaymentToday,
            'visitorPaymentTotal' => $this->visitorPaymentTotal,
            'scanStats' => $this->scanStats,
        ], $extraData));
    }
}
