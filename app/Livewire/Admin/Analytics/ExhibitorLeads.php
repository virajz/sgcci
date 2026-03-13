<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Analytics;

use App\BookingStatus;
use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ExhibitorLeads extends Component
{
    public string $sortBy = 'leads';

    public string $sortDirection = 'desc';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function render(): mixed
    {
        $bookings = Booking::query()
            ->where(function ($q) {
                $q->where('status', BookingStatus::PaymentCompleted)
                    ->orWhere(function ($q2) {
                        $q2->where('status', BookingStatus::PaymentPending)
                            ->whereNotNull('login_password');
                    });
            })
            ->where('is_manual_block', false)
            ->withCount(['leads', 'memberLeads', 'whatsAppInquiries'])
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'brand_name' => $booking->brand_name,
                'leads_count' => $booking->leads_count + $booking->member_leads_count,
                'whatsapp_count' => $booking->whats_app_inquiries_count,
            ]);

        $bookings = match ($this->sortBy) {
            'name' => $bookings->sortBy('brand_name', descending: $this->sortDirection === 'desc'),
            'whatsapp' => $bookings->sortBy('whatsapp_count', descending: $this->sortDirection === 'desc'),
            default => $bookings->sortBy('leads_count', descending: $this->sortDirection === 'desc'),
        };

        return view('livewire.admin.analytics.exhibitor-leads', [
            'bookings' => $bookings->values(),
        ]);
    }
}
