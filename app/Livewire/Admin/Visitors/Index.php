<?php

namespace App\Livewire\Admin\Visitors;

use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showDeleteModal = false;

    public ?int $visitorToDelete = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $visitorId): void
    {
        $this->visitorToDelete = $visitorId;
        $this->showDeleteModal = true;
    }

    public function deleteVisitor(): void
    {
        if (! $this->visitorToDelete) {
            return;
        }

        $visitor = ExhibitionVisitor::findOrFail($this->visitorToDelete);
        $visitor->delete();

        Flux::toast(
            heading: 'Visitor Deleted!',
            variant: 'success',
            text: 'Visitor registration has been removed successfully.'
        );

        $this->showDeleteModal = false;
        $this->visitorToDelete = null;
    }

    public function render()
    {
        $visitors = ExhibitionVisitor::query()
            ->with('exhibition')
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('registration_code', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.visitors.index', [
            'visitors' => $visitors,
            'statuses' => VisitorRegistrationStatus::cases(),
        ]);
    }
}
