<?php

namespace App\Livewire\Admin\StaffMembers;

use App\Models\StaffMember;
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

    public ?int $staffToDelete = null;

    public ?int $staffToEdit = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:10')]
    public string $phoneCode = '+91';

    #[Validate('required|string|max:20')]
    public string $phoneNumber = '';

    #[Validate('boolean')]
    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->reset(['name', 'phoneCode', 'phoneNumber', 'isActive']);
        $this->phoneCode = '+91';
        $this->isActive = true;
        $this->showAddModal = true;
    }

    public function addStaffMember(): void
    {
        $this->validate();

        // Normalize phone number - remove leading + if present in phone number field
        $phoneNumber = ltrim($this->phoneNumber, '+');

        // If phone code is not provided, default to +91 (India)
        if (empty($this->phoneCode)) {
            $this->phoneCode = '+91';
        }

        // Ensure phone code starts with +
        if (! str_starts_with($this->phoneCode, '+')) {
            $this->phoneCode = '+'.$this->phoneCode;
        }

        StaffMember::create([
            'name' => $this->name,
            'phone_code' => $this->phoneCode,
            'phone_number' => $phoneNumber,
            'is_active' => $this->isActive,
        ]);

        Flux::toast(
            heading: 'Staff Member Added!',
            variant: 'success',
            text: 'Staff member has been added successfully.'
        );

        $this->showAddModal = false;
        $this->reset(['name', 'phoneCode', 'phoneNumber', 'isActive']);
    }

    public function openEditModal(int $staffId): void
    {
        $staff = StaffMember::findOrFail($staffId);

        $this->staffToEdit = $staffId;
        $this->name = $staff->name;
        $this->phoneCode = $staff->phone_code;
        $this->phoneNumber = $staff->phone_number;
        $this->isActive = $staff->is_active;

        $this->showEditModal = true;
    }

    public function updateStaffMember(): void
    {
        $this->validate();

        $staff = StaffMember::findOrFail($this->staffToEdit);

        // Normalize phone number
        $phoneNumber = ltrim($this->phoneNumber, '+');

        // Ensure phone code starts with +
        if (! str_starts_with($this->phoneCode, '+')) {
            $this->phoneCode = '+'.$this->phoneCode;
        }

        $staff->update([
            'name' => $this->name,
            'phone_code' => $this->phoneCode,
            'phone_number' => $phoneNumber,
            'is_active' => $this->isActive,
        ]);

        Flux::toast(
            heading: 'Staff Member Updated!',
            variant: 'success',
            text: 'Staff member has been updated successfully.'
        );

        $this->showEditModal = false;
        $this->reset(['staffToEdit', 'name', 'phoneCode', 'phoneNumber', 'isActive']);
    }

    public function confirmDelete(int $staffId): void
    {
        $this->staffToDelete = $staffId;
        $this->showDeleteModal = true;
    }

    public function deleteStaffMember(): void
    {
        if (! $this->staffToDelete) {
            return;
        }

        $staff = StaffMember::findOrFail($this->staffToDelete);
        $staff->delete();

        Flux::toast(
            heading: 'Staff Member Deleted!',
            variant: 'success',
            text: 'Staff member has been removed successfully.'
        );

        $this->showDeleteModal = false;
        $this->staffToDelete = null;
    }

    public function toggleActive(int $staffId): void
    {
        $staff = StaffMember::findOrFail($staffId);
        $staff->update(['is_active' => ! $staff->is_active]);

        $status = $staff->is_active ? 'activated' : 'deactivated';

        Flux::toast(
            heading: 'Status Updated!',
            variant: 'success',
            text: "Staff member has been {$status} successfully."
        );
    }

    public function render()
    {
        $staffMembers = StaffMember::query()
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);
                $phoneSearch = str_replace([' ', '+'], '', $this->search);

                $query->where(function ($q) use ($search, $phoneSearch) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw("REPLACE(REPLACE(phone_number, ' ', ''), '+', '') LIKE ?", ["%{$phoneSearch}%"]);
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.staff-members.index', [
            'staffMembers' => $staffMembers,
        ]);
    }
}
