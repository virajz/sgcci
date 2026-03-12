<?php

namespace App\Livewire\Admin\CommitteeMembers;

use App\Models\CommitteeMember;
use Flux\Flux;
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

    public function render(): \Illuminate\View\View
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
        ]);
    }
}
