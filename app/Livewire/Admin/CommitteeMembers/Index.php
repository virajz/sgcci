<?php

namespace App\Livewire\Admin\CommitteeMembers;

use App\Models\CommitteeMember;
use App\Services\CurrentExhibition;
use App\Services\ExhibitionPassSender;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showAddModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showSendAllModal = false;

    public bool $showSendModal = false;

    public ?int $memberToSend = null;

    public string $memberToSendName = '';

    public ?int $memberToDelete = null;

    public ?int $memberToEdit = null;

    #[Validate('required|integer|min:0')]
    public int $sortOrder = 0;

    #[Validate('nullable|string|max:50')]
    public string $membershipNumber = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $post = '';

    #[Validate('nullable|string|max:255')]
    public string $postForBadge = '';

    #[Validate('nullable|string|max:50')]
    public string $mobile = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->reset(['sortOrder', 'membershipNumber', 'name', 'post', 'postForBadge', 'mobile']);
        $this->showAddModal = true;
    }

    public function addMember(): void
    {
        $this->validate();

        CommitteeMember::create([
            'sort_order' => $this->sortOrder,
            'membership_number' => $this->membershipNumber ?: null,
            'name' => $this->name,
            'post' => $this->post ?: null,
            'post_for_badge' => $this->postForBadge ?: null,
            'mobile' => $this->mobile ?: null,
        ]);

        Flux::toast(heading: 'Member Added!', variant: 'success', text: 'Committee member has been added successfully.');

        $this->showAddModal = false;
        $this->reset(['sortOrder', 'membershipNumber', 'name', 'post', 'postForBadge', 'mobile']);
    }

    public function openEditModal(int $id): void
    {
        $member = CommitteeMember::findOrFail($id);

        $this->memberToEdit = $id;
        $this->sortOrder = $member->sort_order;
        $this->membershipNumber = $member->membership_number ?? '';
        $this->name = $member->name;
        $this->post = $member->post ?? '';
        $this->postForBadge = $member->post_for_badge ?? '';
        $this->mobile = $member->mobile ?? '';

        $this->showEditModal = true;
    }

    public function updateMember(): void
    {
        $this->validate();

        $member = CommitteeMember::findOrFail($this->memberToEdit);

        $member->update([
            'sort_order' => $this->sortOrder,
            'membership_number' => $this->membershipNumber ?: null,
            'name' => $this->name,
            'post' => $this->post ?: null,
            'post_for_badge' => $this->postForBadge ?: null,
            'mobile' => $this->mobile ?: null,
        ]);

        Flux::toast(heading: 'Member Updated!', variant: 'success', text: 'Committee member has been updated successfully.');

        $this->showEditModal = false;
        $this->reset(['memberToEdit', 'sortOrder', 'membershipNumber', 'name', 'post', 'postForBadge', 'mobile']);
    }

    public function confirmDelete(int $id): void
    {
        $this->memberToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteMember(): void
    {
        if (! $this->memberToDelete) {
            return;
        }

        CommitteeMember::findOrFail($this->memberToDelete)->delete();

        Flux::toast(heading: 'Member Deleted!', variant: 'success', text: 'Committee member has been removed successfully.');

        $this->showDeleteModal = false;
        $this->memberToDelete = null;
    }

    public function confirmSend(int $id): void
    {
        if (! CurrentExhibition::isSelected()) {
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending a pass.');

            return;
        }

        $member = CommitteeMember::findOrFail($id);

        $this->memberToSend = $id;
        $this->memberToSendName = $member->name;
        $this->showSendModal = true;
    }

    public function sendWhatsApp(int $id): void
    {
        $exhibition = CurrentExhibition::model();

        if (! $exhibition) {
            $this->showSendModal = false;
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending a pass.');

            return;
        }

        $member = CommitteeMember::findOrFail($id);

        $sent = (new ExhibitionPassSender($exhibition))
            ->sendToContact($member->name, $member->mobile, null, null, 'committee_member');

        $this->showSendModal = false;
        $this->reset(['memberToSend', 'memberToSendName']);

        if (! $sent) {
            Flux::toast(heading: 'No mobile number', variant: 'warning', text: "No mobile number on file for {$member->name}.");

            return;
        }

        Flux::toast(heading: 'WhatsApp Sent!', variant: 'success', text: "Pass sent to {$member->name}.");
    }

    public function confirmSendAll(): void
    {
        if (! CurrentExhibition::isSelected()) {
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending passes.');

            return;
        }

        $this->showSendAllModal = true;
    }

    public function sendWhatsAppToAll(): void
    {
        $exhibition = CurrentExhibition::model();

        if (! $exhibition) {
            $this->showSendAllModal = false;
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending passes.');

            return;
        }

        $sender = new ExhibitionPassSender($exhibition);
        $sent = 0;
        $skipped = 0;

        CommitteeMember::query()->chunkById(200, function ($members) use ($sender, &$sent, &$skipped): void {
            foreach ($members as $member) {
                $sender->sendToContact($member->name, $member->mobile, null, null, 'committee_member')
                    ? $sent++
                    : $skipped++;
            }
        });

        $this->showSendAllModal = false;

        Flux::toast(
            heading: 'WhatsApp Sent!',
            variant: $sent > 0 ? 'success' : 'warning',
            text: $skipped > 0
                ? "Sent {$sent} passes. Skipped {$skipped} (no mobile number)."
                : "Sent {$sent} passes."
        );
    }

    public function render(): View
    {
        $members = CommitteeMember::query()
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(membership_number) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(post) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.committee-members.index', [
            'members' => $members,
            'exhibitionSelected' => CurrentExhibition::isSelected(),
        ]);
    }
}
