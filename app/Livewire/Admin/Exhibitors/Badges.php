<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Exhibitors;

use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Badges extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $booking;
    }

    public function render()
    {
        $scanUrl = route('exhibitor.scan', $this->booking->booking_code);
        $qrSvg = app(QrCodeService::class)->generateSvg($scanUrl, 180);

        return view('livewire.admin.exhibitors.badges', [
            'members' => $this->booking->badgeMembers()->get(),
            'qrSvg' => $qrSvg,
        ]);
    }
}
