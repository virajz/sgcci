<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VisitorScanController extends Controller
{
    public function __invoke(Exhibition $exhibition, string $registrationCode): RedirectResponse
    {
        ExhibitionVisitor::where('registration_code', $registrationCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();

        $user = Auth::user();

        if ($user?->isAdmin()) {
            return redirect()->route('admin.visitors.index', [
                'search' => $registrationCode,
            ]);
        }

        if ($user?->isFrontDesk()) {
            return redirect()->route('front-desk.index', [
                'lookup' => $registrationCode,
            ]);
        }

        return redirect()->route('visitors-registration.thank-you', [
            'exhibition' => $exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);
    }
}
