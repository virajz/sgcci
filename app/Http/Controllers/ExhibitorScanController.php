<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ExhibitorScanController extends Controller
{
    public function __invoke(string $bookingCode): RedirectResponse
    {
        $booking = Booking::where('booking_code', $bookingCode)->firstOrFail();

        $user = Auth::user();

        // Admins → admin badges view for this exhibitor
        if ($user?->isAdmin()) {
            return redirect()->route('admin.exhibitors.badges', $booking);
        }

        // Authenticated exhibitor (their own booking) → their badges page
        if ($user?->isExhibitor() && $user->booking?->id === $booking->id) {
            return redirect()->route('exhibitor.badges.index');
        }

        // Everyone else (visitors, unauthenticated) → WhatsApp
        $message = 'I want to know more about '.$booking->brand_name.' - '.substr($booking->booking_code, -6);
        $whatsappUrl = 'https://wa.me/919979791940?text='.rawurlencode($message);

        return redirect()->away($whatsappUrl);
    }
}
