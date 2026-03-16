<?php

namespace App\Livewire\Admin\Exhibitions;

use App\Models\Exhibition;
use App\Services\QrCodeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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

    public bool $showToggleRegistrationModal = false;

    public bool $showQrModal = false;

    public ?int $exhibitionToEdit = null;

    public ?int $exhibitionToDelete = null;

    public ?int $exhibitionToToggleRegistration = null;

    public string $qrCodeSvg = '';

    public string $qrCodeUrl = '';

    public string $qrExhibitionTitle = '';

    public string $qrSource = '';

    public ?int $qrExhibitionId = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|date')]
    public string $startDate = '';

    #[Validate('required|date|after_or_equal:startDate')]
    public string $endDate = '';

    #[Validate('required|string|in:free,paid')]
    public string $entryType = 'free';

    #[Validate('nullable|numeric|min:1|required_if:entryType,paid')]
    public ?string $entryAmount = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->reset(['title', 'description', 'startDate', 'endDate', 'entryType', 'entryAmount']);
        $this->showAddModal = true;
    }

    public function addExhibition(): void
    {
        $this->validate();

        Exhibition::create([
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'entry_type' => $this->entryType,
            'entry_amount' => $this->entryType === 'paid' ? $this->entryAmount : null,
            'created_by' => Auth::id(),
        ]);

        Flux::toast(
            heading: 'Exhibition Added!',
            variant: 'success',
            text: 'Exhibition has been added successfully.'
        );

        $this->showAddModal = false;
        $this->reset(['title', 'description', 'startDate', 'endDate', 'entryType', 'entryAmount']);
    }

    public function openEditModal(int $exhibitionId): void
    {
        $exhibition = Exhibition::findOrFail($exhibitionId);

        $this->exhibitionToEdit = $exhibitionId;
        $this->title = $exhibition->title;
        $this->description = $exhibition->description ?? '';
        $this->startDate = $exhibition->start_date->format('Y-m-d');
        $this->endDate = $exhibition->end_date->format('Y-m-d');
        $this->entryType = $exhibition->entry_type?->value ?? 'free';
        $this->entryAmount = $exhibition->entry_amount ? (string) $exhibition->entry_amount : null;

        $this->showEditModal = true;
    }

    public function updateExhibition(): void
    {
        $this->validate();

        $exhibition = Exhibition::findOrFail($this->exhibitionToEdit);

        $exhibition->update([
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'entry_type' => $this->entryType,
            'entry_amount' => $this->entryType === 'paid' ? $this->entryAmount : null,
        ]);

        Flux::toast(
            heading: 'Exhibition Updated!',
            variant: 'success',
            text: 'Exhibition has been updated successfully.'
        );

        $this->showEditModal = false;
        $this->reset(['exhibitionToEdit', 'title', 'description', 'startDate', 'endDate', 'entryType', 'entryAmount']);
    }

    public function confirmToggleRegistration(int $exhibitionId): void
    {
        $this->exhibitionToToggleRegistration = $exhibitionId;
        $this->showToggleRegistrationModal = true;
    }

    public function toggleRegistrationClosed(): void
    {
        if (! $this->exhibitionToToggleRegistration) {
            return;
        }

        $exhibition = Exhibition::findOrFail($this->exhibitionToToggleRegistration);
        $exhibition->update(['registration_closed' => ! $exhibition->registration_closed]);

        $nowClosed = $exhibition->fresh()->registration_closed;

        Flux::toast(
            heading: $nowClosed ? 'Registration Closed' : 'Registration Opened',
            variant: 'success',
            text: $nowClosed
                ? 'Visitor registration has been closed.'
                : 'Visitor registration is now open.'
        );

        $this->showToggleRegistrationModal = false;
        $this->exhibitionToToggleRegistration = null;
    }

    public function confirmDelete(int $exhibitionId): void
    {
        $this->exhibitionToDelete = $exhibitionId;
        $this->showDeleteModal = true;
    }

    public function deleteExhibition(): void
    {
        if (! $this->exhibitionToDelete) {
            return;
        }

        $exhibition = Exhibition::findOrFail($this->exhibitionToDelete);
        $exhibition->delete();

        Flux::toast(
            heading: 'Exhibition Deleted!',
            variant: 'success',
            text: 'Exhibition has been removed successfully.'
        );

        $this->showDeleteModal = false;
        $this->exhibitionToDelete = null;
    }

    public function showQrCode(int $exhibitionId): void
    {
        $exhibition = Exhibition::findOrFail($exhibitionId);

        $this->qrExhibitionId = $exhibitionId;
        $this->qrExhibitionTitle = $exhibition->title;
        $this->qrSource = '';
        $this->generateQrCode($exhibition);

        $this->showQrModal = true;
    }

    public function updatedQrSource(): void
    {
        if (! $this->qrExhibitionId) {
            return;
        }

        $exhibition = Exhibition::findOrFail($this->qrExhibitionId);
        $this->generateQrCode($exhibition);
    }

    private function generateQrCode(Exhibition $exhibition): void
    {
        $this->qrCodeUrl = url("/{$exhibition->slug}/visitors-registration");

        if (! empty(trim($this->qrSource))) {
            $this->qrCodeUrl .= '?source='.urlencode(trim($this->qrSource));
        }

        $this->qrCodeSvg = app(QrCodeService::class)->generateSvg($this->qrCodeUrl);
    }

    public function render()
    {
        $exhibitions = Exhibition::query()
            ->with('createdBy')
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.exhibitions.index', [
            'exhibitions' => $exhibitions,
        ]);
    }
}
