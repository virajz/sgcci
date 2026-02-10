<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Services\QrCodeService;
use Illuminate\Http\Response;

class VisitorPassController extends Controller
{
    public function __construct(
        public QrCodeService $qrCodeService
    ) {}

    public function download(Exhibition $exhibition, string $registrationCode): Response
    {
        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();

        // Generate QR code URL
        $qrCodeUrl = url("/{$exhibition->slug}/visitors/{$visitor->id}/{$registrationCode}");

        // Generate QR code PNG using the service
        $qrImageData = $this->qrCodeService->generatePng($qrCodeUrl);

        return response($qrImageData)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="qr-code-' . $registrationCode . '.png"');
    }
}
