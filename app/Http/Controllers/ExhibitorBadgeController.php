<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\BookingStatus;
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

    public function downloadSvg(Booking $booking): Response
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $scanUrl = route('exhibitor.scan', $booking->booking_code);
        $svgContent = $this->qrCodeService->generateSvg($scanUrl, 400);
        $filename = $booking->brand_name.' - '.$booking->booking_code.'.svg';

        return response($svgContent)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function downloadAllSvgs(): Response
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $bookings = Booking::query()
            ->where(function ($q) {
                $q->where('status', BookingStatus::PaymentCompleted)
                    ->orWhere(function ($q2) {
                        $q2->where('status', BookingStatus::PaymentPending)
                            ->where('amount_paid', '>', 0)
                            ->whereNotNull('login_password');
                    });
            })
            ->where('is_manual_block', false)
            ->get(['id', 'brand_name', 'booking_code']);

        $zip = new ZipArchive;
        $tmpPath = tempnam(sys_get_temp_dir(), 'exhibitor_svgs_').'zip';
        $zip->open($tmpPath, ZipArchive::CREATE);

        foreach ($bookings as $booking) {
            $scanUrl = route('exhibitor.scan', $booking->booking_code);
            $svgContent = $this->qrCodeService->generateSvg($scanUrl, 400);
            $filename = $booking->brand_name.' - '.$booking->booking_code.'.svg';
            $zip->addFromString($filename, $svgContent);
        }

        $zip->close();
        $zipData = file_get_contents($tmpPath);
        unlink($tmpPath);

        return response($zipData)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="exhibitor-svgs.zip"');
    }

    public function profileMedia(Booking $booking): Response
    {
        $this->authorizeBookingAccess($booking);

        abort_unless($booking->profile_message_media && Storage::exists($booking->profile_message_media), 404);

        $contents = Storage::get($booking->profile_message_media);
        $mime = Storage::mimeType($booking->profile_message_media) ?: 'application/octet-stream';

        return response($contents)->header('Content-Type', $mime);
    }

    public function profileMedia2(Booking $booking): Response
    {
        $this->authorizeBookingAccess($booking);

        abort_unless($booking->profile_message_2_media && Storage::exists($booking->profile_message_2_media), 404);

        $contents = Storage::get($booking->profile_message_2_media);
        $mime = Storage::mimeType($booking->profile_message_2_media) ?: 'application/octet-stream';

        return response($contents)->header('Content-Type', $mime);
    }

    public function photo(Booking $booking, ExhibitorBadgeMember $member): Response
    {
        $this->authorizeAccess($booking, $member);

        abort_unless($member->photo && Storage::exists($member->photo), 404);

        $contents = Storage::get($member->photo);
        $mime = Storage::mimeType($member->photo) ?: 'image/jpeg';

        return response($contents)->header('Content-Type', $mime);
    }

    public function printAll(Booking $booking): \Illuminate\View\View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $members = $booking->badgeMembers()->get();

        $badges = $members->map(fn (ExhibitorBadgeMember $member) => [
            'name' => $member->name,
            'url' => route('exhibitor.badges.inline', [$booking, $member]),
        ])->all();

        return view('front-desk.print-badge', compact('badges'));
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
