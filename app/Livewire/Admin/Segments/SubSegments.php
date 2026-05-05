<?php

namespace App\Livewire\Admin\Segments;

use App\Models\Segment;
use App\Models\SubSegment;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SubSegments extends Component
{
    use WithPagination;

    #[Locked]
    public int $segmentId;

    public string $search = '';

    public bool $showAddModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $subSegmentToEdit = null;

    public ?int $subSegmentToDelete = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('integer|min:0|max:9999')]
    public int $sortOrder = 0;

    #[Validate('boolean')]
    public bool $isActive = true;

    public function mount(Segment $segment): void
    {
        $this->segmentId = $segment->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->showAddModal = true;
    }

    public function addSubSegment(): void
    {
        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:sub_segments,name,NULL,id,segment_id,'.$this->segmentId,
            ],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
            'isActive' => ['boolean'],
        ]);

        SubSegment::create([
            'segment_id' => $this->segmentId,
            'name' => $data['name'],
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ]);

        Flux::toast(
            heading: 'Sub-segment Added!',
            variant: 'success',
            text: 'Sub-segment has been added successfully.'
        );

        $this->showAddModal = false;
        $this->resetForm();
    }

    public function openEditModal(int $subSegmentId): void
    {
        $subSegment = SubSegment::where('segment_id', $this->segmentId)
            ->findOrFail($subSegmentId);

        $this->subSegmentToEdit = $subSegmentId;
        $this->name = $subSegment->name;
        $this->sortOrder = $subSegment->sort_order;
        $this->isActive = $subSegment->is_active;
        $this->showEditModal = true;
    }

    public function updateSubSegment(): void
    {
        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:sub_segments,name,'.$this->subSegmentToEdit.',id,segment_id,'.$this->segmentId,
            ],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
            'isActive' => ['boolean'],
        ]);

        $subSegment = SubSegment::where('segment_id', $this->segmentId)
            ->findOrFail($this->subSegmentToEdit);

        $subSegment->update([
            'name' => $data['name'],
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ]);

        Flux::toast(
            heading: 'Sub-segment Updated!',
            variant: 'success',
            text: 'Sub-segment has been updated successfully.'
        );

        $this->showEditModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $subSegmentId): void
    {
        $this->subSegmentToDelete = $subSegmentId;
        $this->showDeleteModal = true;
    }

    public function deleteSubSegment(): void
    {
        if (! $this->subSegmentToDelete) {
            return;
        }

        $subSegment = SubSegment::where('segment_id', $this->segmentId)
            ->findOrFail($this->subSegmentToDelete);
        $subSegment->delete();

        Flux::toast(
            heading: 'Sub-segment Deleted!',
            variant: 'success',
            text: 'Sub-segment has been removed.'
        );

        $this->showDeleteModal = false;
        $this->subSegmentToDelete = null;
    }

    public function toggleActive(int $subSegmentId): void
    {
        $subSegment = SubSegment::where('segment_id', $this->segmentId)
            ->findOrFail($subSegmentId);
        $subSegment->update(['is_active' => ! $subSegment->is_active]);

        Flux::toast(
            heading: 'Status Updated!',
            variant: 'success',
            text: 'Sub-segment has been '.($subSegment->is_active ? 'activated' : 'deactivated').'.'
        );
    }

    private function resetForm(): void
    {
        $this->reset(['subSegmentToEdit', 'name', 'sortOrder', 'isActive']);
        $this->isActive = true;
    }

    public function render()
    {
        $segment = Segment::findOrFail($this->segmentId);

        $subSegments = SubSegment::query()
            ->where('segment_id', $this->segmentId)
            ->when($this->search !== '', function ($query) {
                $search = strtolower($this->search);
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            })
            ->ordered()
            ->paginate(25);

        return view('livewire.admin.segments.sub-segments', [
            'segment' => $segment,
            'subSegments' => $subSegments,
        ]);
    }
}
