<?php

namespace App\Livewire\Admin;

use App\Models\Booking;
use App\Models\Exhibition;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StallBlockManager extends Component
{
    public $showBlockModal = false;

    public $showReleaseModal = false;

    public $showConfirmReleaseModal = false;

    public $stallToRelease = null;

    public $stallNumbers = '';

    public $exhibitionId;

    public function mount(): void
    {
        // Ensure user has admin privileges
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Get the latest exhibition or allow selection
        $this->exhibitionId = Exhibition::latest()->first()?->id;
    }

    public function openBlockModal(): void
    {
        $this->showBlockModal = true;
        $this->stallNumbers = '';
    }

    public function openReleaseModal(): void
    {
        $this->showReleaseModal = true;
    }

    public function confirmRelease(int $bookingId): void
    {
        $this->stallToRelease = $bookingId;
        $this->showConfirmReleaseModal = true;
    }

    public function blockStalls(): void
    {
        // Security check
        if (! Auth::user()->isAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only admins can block stalls.'
            );

            return;
        }

        $this->validate([
            'stallNumbers' => 'required|string',
            'exhibitionId' => 'required|exists:exhibitions,id',
        ]);

        // Parse comma-separated stall numbers
        $stalls = array_map('trim', explode(',', $this->stallNumbers));
        $stalls = array_filter($stalls); // Remove empty values

        if (empty($stalls)) {
            $this->addError('stallNumbers', 'Please enter at least one stall number.');

            return;
        }

        $created = 0;
        foreach ($stalls as $stallNumber) {
            // Check if stall is already blocked or booked
            $exists = Booking::where('exhibition_id', $this->exhibitionId)
                ->whereJsonContains('selected_stalls', $stallNumber)
                ->exists();

            if (! $exists) {
                Booking::create([
                    'exhibition_id' => $this->exhibitionId,
                    'booking_code' => Booking::generateUniqueBookingCode(),
                    'brand_name' => 'Manual Block',
                    'contact_person' => 'Admin',
                    'phone_code' => '+91',
                    'phone_number' => '0000000000',
                    'email' => 'admin@sgcci.com',
                    'city' => 'Admin',
                    'product_profile' => ['Manual Block'],
                    'selected_stalls' => [$stallNumber],
                    'total_area' => 0,
                    'price_per_sqm' => 0,
                    'total_price' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'price_after_discount' => 0,
                    'gst_amount' => 0,
                    'total_with_gst' => 0,
                    'status' => 'payment_completed',
                    'is_manual_block' => true,
                    'blocked_by' => Auth::id(),
                    'blocked_at' => now(),
                ]);
                $created++;
            }
        }

        $this->showBlockModal = false;
        $this->stallNumbers = '';

        if ($created > 0) {
            Flux::toast(
                heading: 'Stalls Blocked!',
                variant: 'success',
                text: "{$created} stall(s) blocked successfully."
            );
            $this->dispatch('stalls-blocked');
        } else {
            Flux::toast(
                heading: 'No Changes Made',
                variant: 'warning',
                text: 'All specified stalls are already blocked or booked.'
            );
        }

        $this->dispatch('refresh-inquiries');
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

            $this->dispatch('stall-released');
            $this->dispatch('refresh-inquiries');
        }

        $this->showConfirmReleaseModal = false;
        $this->stallToRelease = null;
    }

    public function render()
    {
        $blockedStalls = Booking::where('is_manual_block', true)
            ->where('exhibition_id', $this->exhibitionId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.admin.stall-block-manager', [
            'blockedStalls' => $blockedStalls,
        ]);
    }
}
