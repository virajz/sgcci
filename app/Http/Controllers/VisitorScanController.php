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
            $params = ['lookup' => $registrationCode];

            if (request()->filled('person')) {
                $params['person'] = request()->query('person');
            }

            return redirect()->route('front-desk.index', $params);
        }

        if ($user?->isSecurityDesk()) {
            $params = ['lookup' => $registrationCode];

            if (request()->filled('person')) {
                $params['person'] = request()->query('person');
            }

            return redirect()->route('security-desk.index', $params);
        }

        return redirect()->route('visitors-registration.thank-you', [
            'exhibition' => $exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);
    }
}
