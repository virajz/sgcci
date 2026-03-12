<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Create extends Component
{
    #[Validate('required|string|max:50|unique:members,membership_number')]
    public string $membershipNumber = '';

    #[Validate('required|string|max:255')]
    public string $contactName = '';

    #[Validate('nullable|string|max:255')]
    public string $company = '';

    public function save(): void
    {
        $this->validate();

        $member = Member::create([
            'membership_number' => $this->membershipNumber,
            'contact_name' => $this->contactName,
            'company' => $this->company ?: null,
        ]);

        Flux::toast(heading: 'Member Created!', variant: 'success', text: 'Member has been created. Fill in additional details below.');

        $this->redirectRoute('admin.members.show', $member, navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.members.create');
    }
}
