<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CommitteeMember;
use App\Models\Member;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MemberBadgeController extends Controller
{
    public function __construct(public QrCodeService $qrCodeService) {}

    public function committeeInline(CommitteeMember $committeeMember): Response
    {
        $photoPath = $committeeMember->photo && Storage::exists($committeeMember->photo)
            ? Storage::path($committeeMember->photo)
            : null;

        $imageData = $this->qrCodeService->generateCommitteeBadgeImage(
            memberName: $committeeMember->name,
            membershipNumber: $committeeMember->membership_number,
            post: $committeeMember->post_for_badge ?? $committeeMember->post,
            photoPath: $photoPath,
        );

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'inline');
    }

    public function committeePrint(CommitteeMember $committeeMember): View
    {
        $badges = [[
            'name' => $committeeMember->name,
            'url' => route('admin.committee-members.badge.inline', $committeeMember),
        ]];

        return view('front-desk.print-badge', compact('badges'));
    }

    public function memberInline(Member $member): Response
    {
        $imageData = $this->qrCodeService->generateSgcciMemberBadgeImage(
            memberName: $member->contact_name,
            membershipNumber: $member->membership_number,
            companyName: $member->company,
        );

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'inline');
    }

    public function memberPrint(Member $member): View
    {
        $badges = [[
            'name' => $member->contact_name,
            'url' => route('admin.members.badge.inline', $member),
        ]];

        return view('front-desk.print-badge', compact('badges'));
    }
}
