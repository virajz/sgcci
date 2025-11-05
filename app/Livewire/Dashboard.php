<?php

namespace App\Livewire;

use App\BookingStatus;
use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    // Total stalls available in the exhibition (based on the map)
    private const TOTAL_STALLS = 104;

    public function getAvailableStallsProperty(): int
    {
        $exhibition = \App\Models\Exhibition::first();

        if (! $exhibition) {
            return self::TOTAL_STALLS;
        }

        // Get all stalls that are currently booked (not rejected, cancelled, or expired)
        $bookedStallsCount = Booking::where('exhibition_id', $exhibition->id)
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
        $manualBlockCount = Booking::where('exhibition_id', $exhibition->id)
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

        return Booking::where('status', BookingStatus::PaymentPending)
            ->where('is_manual_block', false)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', $threeDaysFromNow)
            ->where('payment_due_at', '>=', now())
            ->count();
    }

    public function getBookedStallsProperty(): int
    {
        $exhibition = \App\Models\Exhibition::first();

        if (! $exhibition) {
            return 0;
        }

        return Booking::where('exhibition_id', $exhibition->id)
            ->where('status', BookingStatus::PaymentCompleted)
            ->where('is_manual_block', false)
            ->count();
    }

    public function getPendingReviewsProperty(): int
    {
        return Booking::where('status', BookingStatus::PendingApproval)
            ->where('is_manual_block', false)
            ->count();
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'availableStalls' => $this->availableStalls,
            'paymentsDue' => $this->paymentsDue,
            'bookedStalls' => $this->bookedStalls,
            'pendingReviews' => $this->pendingReviews,
        ]);
    }
}
