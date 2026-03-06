<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Exhibitors;

use App\Models\Booking;
use App\Services\QrCodeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Badges extends Component
{
    use WithFileUploads;

    public Booking $booking;

    public bool $showAddModal = false;

    public ?int $editingMemberId = null;

    public ?int $selectedMemberId = null;

    public ?int $deletingMemberId = null;

    public string $deletingMemberName = '';

    public string $memberName = '';

    public string $memberPhoneNumber = '';

    public $memberPhoto = null;

    public function mount(Booking $booking): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $booking;
        $this->selectedMemberId = $booking->badgeMembers()->value('id');
    }

    public function openAddModal(): void
    {
        $this->resetMemberForm();
        $this->showAddModal = true;
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
            'phone_number' => $this->memberPhoneNumber ?: null,
            'photo' => $photoPath,
        ]);

        $this->showAddModal = false;
        $this->resetMemberForm();

        Flux::toast(heading: 'Member Added', variant: 'success', text: 'Badge member added successfully.');
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
            'phone_number' => $this->memberPhoneNumber ?: null,
            'photo' => $photoPath,
        ]);

        $this->resetMemberForm();

        Flux::toast(heading: 'Member Updated', variant: 'success', text: 'Badge member updated successfully.');
    }

    public function deleteMember(): void
    {
        $member = $this->booking->badgeMembers()->findOrFail($this->deletingMemberId);

        if ($member->photo) {
            Storage::delete($member->photo);
        }

        $deletedId = $member->id;
        $member->delete();
        $this->deletingMemberId = null;
        $this->deletingMemberName = '';

        if ($this->selectedMemberId === $deletedId) {
            $this->selectedMemberId = $this->booking->badgeMembers()->value('id');
        }

        $this->dispatch('member-deleted', selectedMemberId: $this->selectedMemberId);

        Flux::toast(heading: 'Member Removed', variant: 'success', text: 'Badge member removed.');
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
        $qrSvg = app(QrCodeService::class)->generateSvg($scanUrl, 80);

        $members = $this->booking->badgeMembers()->get();

        return view('livewire.admin.exhibitors.badges', [
            'members' => $members,
            'selectedMember' => $members->firstWhere('id', $this->selectedMemberId),
            'qrSvg' => $qrSvg,
        ]);
    }
}
