<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitor;

use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Badges extends Component
{
    use WithFileUploads;

    public ?Booking $booking = null;

    public bool $showAddModal = false;

    public bool $showEditModal = false;

    public bool $showBadgePreviewModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingMemberId = null;

    public ?int $previewingMemberId = null;

    public ?int $deletingMemberId = null;

    public string $deletingMemberName = '';

    public string $memberName = '';

    public string $memberPhoneNumber = '';

    public $memberPhoto = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user->isExhibitor()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $user->booking;

        if (! $this->booking) {
            abort(404, 'No booking found for this exhibitor.');
        }
    }

    public function openBadgePreview(int $memberId): void
    {
        $this->previewingMemberId = $memberId;
        $this->showBadgePreviewModal = true;
    }

    public function openAddModal(): void
    {
        $this->resetMemberForm();
        $this->showAddModal = true;
    }

    public function openEditModal(int $memberId): void
    {
        $member = $this->booking->badgeMembers()->findOrFail($memberId);
        $this->editingMemberId = $memberId;
        $this->memberName = $member->name;
        $this->memberPhoneNumber = $member->phone_number ?? '';
        $this->memberPhoto = null;
        $this->showEditModal = true;
    }

    public function addMember(): void
    {
        $this->validate([
            'memberName' => ['required', 'string', 'max:255'],
            'memberPhoneNumber' => ['required', 'string', 'max:20'],
            'memberPhoto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->booking->badgeMembers()->count() >= $this->booking->badge_limit) {
            $this->addError('memberName', 'Badge limit of '.$this->booking->badge_limit.' reached.');

            return;
        }

        $photoPath = null;

        if ($this->memberPhoto) {
            $photoPath = $this->memberPhoto->store('badge-photos');
        }

        $this->booking->badgeMembers()->create([
            'name' => $this->memberName,
            'phone_number' => $this->memberPhoneNumber,
            'photo' => $photoPath,
        ]);

        $this->showAddModal = false;
        $this->resetMemberForm();
        $this->dispatch('member-added');
    }

    public function updateMember(): void
    {
        $this->validate([
            'memberName' => ['required', 'string', 'max:255'],
            'memberPhoneNumber' => ['required', 'string', 'max:20'],
            'memberPhoto' => ['nullable', 'image', 'max:2048'],
        ]);

        $member = $this->booking->badgeMembers()->findOrFail($this->editingMemberId);

        $photoPath = $member->photo;

        if ($this->memberPhoto) {
            if ($photoPath) {
                Storage::delete($photoPath);
            }
            $photoPath = $this->memberPhoto->store('badge-photos');
        }

        $member->update([
            'name' => $this->memberName,
            'phone_number' => $this->memberPhoneNumber,
            'photo' => $photoPath,
        ]);

        $this->showEditModal = false;
        $this->resetMemberForm();
        $this->dispatch('member-updated');
    }

    public function confirmDelete(int $memberId): void
    {
        $member = $this->booking->badgeMembers()->findOrFail($memberId);
        $this->deletingMemberId = $memberId;
        $this->deletingMemberName = $member->name;
        $this->showDeleteModal = true;
    }

    public function deleteMember(): void
    {
        $member = $this->booking->badgeMembers()->findOrFail($this->deletingMemberId);

        if ($member->photo) {
            Storage::delete($member->photo);
        }

        $member->delete();
        $this->showDeleteModal = false;
        $this->deletingMemberId = null;
        $this->deletingMemberName = '';
        $this->dispatch('member-deleted');
    }

    private function resetMemberForm(): void
    {
        $this->memberName = '';
        $this->memberPhoneNumber = '';
        $this->memberPhoto = null;
        $this->editingMemberId = null;
    }

    public function render()
    {
        $scanUrl = route('exhibitor.scan', $this->booking->booking_code);
        $qrSvg = app(QrCodeService::class)->generateSvg($scanUrl, 220);

        return view('livewire.exhibitor.badges', [
            'members' => $this->booking->badgeMembers()->get(),
            'qrSvg' => $qrSvg,
            'scanUrl' => $scanUrl,
        ]);
    }
}
