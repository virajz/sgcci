<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ExhibitorBadgeMember;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExhibitorBadgeController extends Controller
{
    public function __construct(public QrCodeService $qrCodeService) {}

    public function inline(Booking $booking, ExhibitorBadgeMember $member): Response
    {
        $this->authorizeAccess($booking, $member);

        $imageData = $this->buildBadgeImage($booking, $member);

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'inline');
    }

    public function download(Booking $booking, ExhibitorBadgeMember $member): Response
    {
        $this->authorizeAccess($booking, $member);

        $imageData = $this->buildBadgeImage($booking, $member);
        $filename = 'badge-'.str($member->name)->slug().'.jpg';

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function downloadAll(Booking $booking): Response
    {
        $this->authorizeBookingAccess($booking);

        $members = $booking->badgeMembers()->get();
        $zip = new ZipArchive;
        $tmpPath = tempnam(sys_get_temp_dir(), 'badges_').'zip';
        $zip->open($tmpPath, ZipArchive::CREATE);

        foreach ($members as $member) {
            $imageData = $this->buildBadgeImage($booking, $member);
            $filename = 'badge-'.str($member->name)->slug().'.jpg';
            $zip->addFromString($filename, $imageData);
        }

        $zip->close();
        $zipData = file_get_contents($tmpPath);
        unlink($tmpPath);

        $zipFilename = 'badges-'.str($booking->brand_name)->slug().'.zip';

        return response($zipData)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="'.$zipFilename.'"');
    }

    private function buildBadgeImage(Booking $booking, ExhibitorBadgeMember $member): string
    {
        $scanUrl = route('exhibitor.scan', $booking->booking_code);

        // Write photo to a temp file so Imagick can read it regardless of storage driver
        $tmpPhotoPath = null;
        if ($member->photo && Storage::exists($member->photo)) {
            $tmpPhotoPath = tempnam(sys_get_temp_dir(), 'badge_photo_');
            file_put_contents($tmpPhotoPath, Storage::get($member->photo));
        }

        try {
            return $this->qrCodeService->generateBadgeImage(
                qrData: $scanUrl,
                memberName: $member->name,
                companyName: $booking->brand_name,
                stallNumbers: implode(', ', $booking->selected_stalls),
                photoPath: $tmpPhotoPath,
            );
        } finally {
            if ($tmpPhotoPath && file_exists($tmpPhotoPath)) {
                unlink($tmpPhotoPath);
            }
        }
    }

    private function authorizeAccess(Booking $booking, ExhibitorBadgeMember $member): void
    {
        abort_unless($member->booking_id === $booking->id, 404);
        $this->authorizeBookingAccess($booking);
    }

    private function authorizeBookingAccess(Booking $booking): void
    {
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return;
        }

        if ($user?->isExhibitor() && $user->booking?->id === $booking->id) {
            return;
        }

        abort(403);
    }
}
