<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $members = Member::query()
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(contact_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(membership_number) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('REPLACE(LOWER(cell_no), \' \', \'\') LIKE ?', ['%'.str_replace(' ', '', $search).'%']);
                });
            })
            ->orderBy('membership_number')
            ->paginate(20);

        return view('livewire.admin.members.index', [
            'members' => $members,
        ]);
    }
}
