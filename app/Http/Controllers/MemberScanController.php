<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class MemberScanController extends Controller
{
    public function __invoke(string $membershipNumber): RedirectResponse
    {
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return redirect()->route('admin.members.index', ['search' => $membershipNumber]);
        }

        if ($user?->isFrontDesk()) {
            return redirect()->route('front-desk.index', ['lookup' => $membershipNumber]);
        }

        if ($user?->isSecurityDesk()) {
            return redirect()->route('security-desk.index', ['lookup' => $membershipNumber]);
        }

        // Unauthenticated — send to login
        return redirect()->route('login');
    }
}
