<?php

namespace App\Livewire\Admin\Segments;

use App\Models\Segment;
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

    public ?int $segmentToEdit = null;

    public ?int $segmentToDelete = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('integer|min:0|max:9999')]
    public int $sortOrder = 0;

    #[Validate('boolean')]
    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->showAddModal = true;
    }

    public function addSegment(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:segments,name'],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
            'isActive' => ['boolean'],
        ]);

        Segment::create([
            'name' => $data['name'],
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ]);

        Flux::toast(
            heading: 'Segment Added!',
            variant: 'success',
            text: 'Segment has been added successfully.'
        );

        $this->showAddModal = false;
        $this->resetForm();
    }

    public function openEditModal(int $segmentId): void
    {
        $segment = Segment::findOrFail($segmentId);

        $this->segmentToEdit = $segmentId;
        $this->name = $segment->name;
        $this->sortOrder = $segment->sort_order;
        $this->isActive = $segment->is_active;
        $this->showEditModal = true;
    }

    public function updateSegment(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:segments,name,'.$this->segmentToEdit],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
            'isActive' => ['boolean'],
        ]);

        $segment = Segment::findOrFail($this->segmentToEdit);
        $segment->update([
            'name' => $data['name'],
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ]);

        Flux::toast(
            heading: 'Segment Updated!',
            variant: 'success',
            text: 'Segment has been updated successfully.'
        );

        $this->showEditModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $segmentId): void
    {
        $this->segmentToDelete = $segmentId;
        $this->showDeleteModal = true;
    }

    public function deleteSegment(): void
    {
        if (! $this->segmentToDelete) {
            return;
        }

        $segment = Segment::findOrFail($this->segmentToDelete);
        $segment->delete();

        Flux::toast(
            heading: 'Segment Deleted!',
            variant: 'success',
            text: 'Segment and its sub-segments have been removed.'
        );

        $this->showDeleteModal = false;
        $this->segmentToDelete = null;
    }

    public function toggleActive(int $segmentId): void
    {
        $segment = Segment::findOrFail($segmentId);
        $segment->update(['is_active' => ! $segment->is_active]);

        Flux::toast(
            heading: 'Status Updated!',
            variant: 'success',
            text: 'Segment has been '.($segment->is_active ? 'activated' : 'deactivated').'.'
        );
    }

    private function resetForm(): void
    {
        $this->reset(['segmentToEdit', 'name', 'sortOrder', 'isActive']);
        $this->isActive = true;
    }

    public function render()
    {
        $segments = Segment::query()
            ->withCount('subSegments')
            ->when($this->search !== '', function ($query) {
                $search = strtolower($this->search);
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            })
            ->ordered()
            ->paginate(20);

        return view('livewire.admin.segments.index', [
            'segments' => $segments,
        ]);
    }
}
