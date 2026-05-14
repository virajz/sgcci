<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WalkInVisitors;

use App\Models\ExhibitionVisitor;
use App\Services\CurrentExhibition;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $visitorTypeFilter = '';

    public bool $showDeleteModal = false;

    public ?int $visitorToDelete = null;

    public bool $showPersonsModal = false;

    public string $selectedVisitorName = '';

    /** @var array<int, array{name: string}> */
    public array $selectedPersons = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingVisitorTypeFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $visitorId): void
    {
        $this->visitorToDelete = $visitorId;
        $this->showDeleteModal = true;
    }

    public function viewPersons(int $visitorId): void
    {
        $visitor = ExhibitionVisitor::findOrFail($visitorId);

        $this->selectedVisitorName = $visitor->name;
        $this->selectedPersons = $visitor->additional_persons ?? [];
        $this->showPersonsModal = true;
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
            text: 'Walk-in visitor registration has been removed successfully.'
        );

        $this->showDeleteModal = false;
        $this->visitorToDelete = null;
    }

    public function render(): View
    {
        $exhibition = CurrentExhibition::model();

        $baseQuery = ExhibitionVisitor::query()
            ->where('source', 'front_desk')
            ->when($exhibition, fn ($q) => $q->where('exhibition_id', $exhibition->id));

        $typeCounts = (clone $baseQuery)
            ->selectRaw("COALESCE(visitor_type, 'standard') as type, COUNT(*) as count")
            ->groupBy('type')
            ->pluck('count', 'type');

        $visitors = (clone $baseQuery)
            ->with('exhibition')
            ->when($this->visitorTypeFilter === 'standard', fn ($q) => $q->whereNull('visitor_type'))
            ->when($this->visitorTypeFilter && $this->visitorTypeFilter !== 'standard', fn ($q) => $q->where('visitor_type', $this->visitorTypeFilter))
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereRaw('LOWER(registration_code) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.walk-in-visitors.index', [
            'visitors' => $visitors,
            'exhibition' => $exhibition,
            'typeCounts' => $typeCounts,
            'totalCount' => $typeCounts->sum(),
        ]);
    }
}
