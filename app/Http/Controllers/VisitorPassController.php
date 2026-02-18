<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
}
