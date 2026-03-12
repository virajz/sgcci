<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VisitorPassController extends Controller
{
    public function __construct(
        public QrCodeService $qrCodeService
    ) {}

    public function download(Exhibition $exhibition, string $registrationCode, Request $request): Response
    {
        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();

        $personIndex = $request->query('personIndex');

        $baseUrl = route('visitor.scan', [
            'exhibition' => $exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);

        if ($personIndex !== null && is_array($visitor->additional_persons) && isset($visitor->additional_persons[(int) $personIndex])) {
            $qrCodeUrl = $baseUrl.'?person='.((int) $personIndex + 1);
            $personName = $visitor->additional_persons[(int) $personIndex]['name'];
            $filename = 'visitor-pass-'.$registrationCode.'-person-'.((int) $personIndex + 1).'.jpg';
        } else {
            $qrCodeUrl = $baseUrl;
            $personName = $visitor->name;
            $filename = 'visitor-pass-'.$registrationCode.'.jpg';
        }

        $imageData = $this->qrCodeService->generateVisitorPassImage($qrCodeUrl, $personName);

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function inline(Exhibition $exhibition, string $registrationCode, Request $request): Response
    {
        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();

        $personIndex = $request->query('personIndex');

        $baseUrl = route('visitor.scan', [
            'exhibition' => $exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);

        if ($personIndex !== null && is_array($visitor->additional_persons) && isset($visitor->additional_persons[(int) $personIndex])) {
            $qrCodeUrl = $baseUrl.'?person='.((int) $personIndex + 1);
            $personName = $visitor->additional_persons[(int) $personIndex]['name'];
        } else {
            $qrCodeUrl = $baseUrl;
            $personName = $visitor->name;
        }

        $imageData = $this->qrCodeService->generateVisitorPassImage($qrCodeUrl, $personName);

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'inline');
    }

    public function walkInBadgeInline(string $registrationCode, Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isFrontDesk()), 403);

        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->with('exhibition')
            ->firstOrFail();

        $scanUrl = route('visitor.scan', [
            'exhibition' => $visitor->exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);

        $personIndex = $request->query('personIndex');

        if ($personIndex !== null && is_array($visitor->additional_persons) && isset($visitor->additional_persons[(int) $personIndex])) {
            $visitorName = $visitor->additional_persons[(int) $personIndex]['name'];
        } else {
            $visitorName = $visitor->name;
        }

        $imageData = $this->qrCodeService->generateWalkInBadgeImage(
            qrData: $scanUrl,
            visitorName: $visitorName,
            companyName: $visitor->company_name ?? '',
            registrationCode: $registrationCode,
            badgeLabel: $visitor->visitor_type?->badgeLabel(),
        );

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'inline');
    }

    public function walkInBadgeDownload(string $registrationCode): Response
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isFrontDesk()), 403);

        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->with('exhibition')
            ->firstOrFail();

        $scanUrl = route('visitor.scan', [
            'exhibition' => $visitor->exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);

        $imageData = $this->qrCodeService->generateWalkInBadgeImage(
            qrData: $scanUrl,
            visitorName: $visitor->name,
            companyName: $visitor->company_name ?? '',
            registrationCode: $registrationCode,
            badgeLabel: $visitor->visitor_type?->badgeLabel(),
        );

        $filename = 'badge-'.str($visitor->name)->slug().'.jpg';

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function printBadge(string $registrationCode, Request $request): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isFrontDesk()), 403);

        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->with('exhibition')
            ->firstOrFail();

        $personIndex = $request->query('personIndex');

        if ($personIndex !== null) {
            // Single person badge
            $personIdx = (int) $personIndex;
            if (is_array($visitor->additional_persons) && isset($visitor->additional_persons[$personIdx])) {
                $visitorName = $visitor->additional_persons[$personIdx]['name'];
            } else {
                $visitorName = $visitor->name;
            }

            $badgeUrl = route('front-desk.visitor.badge.inline', [
                'registrationCode' => $registrationCode,
                'personIndex' => $personIndex,
            ]);

            $badges = [['name' => $visitorName, 'url' => $badgeUrl]];
        } else {
            // All persons — primary + additional, one per page
            $badges = [[
                'name' => $visitor->name,
                'url' => route('front-desk.visitor.badge.inline', ['registrationCode' => $registrationCode]),
            ]];

            if (is_array($visitor->additional_persons)) {
                foreach ($visitor->additional_persons as $idx => $person) {
                    $badges[] = [
                        'name' => $person['name'],
                        'url' => route('front-desk.visitor.badge.inline', [
                            'registrationCode' => $registrationCode,
                            'personIndex' => $idx,
                        ]),
                    ];
                }
            }
        }

        return view('front-desk.print-badge', compact('badges'));
    }
}
