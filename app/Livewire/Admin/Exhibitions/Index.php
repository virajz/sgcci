<?php

namespace App\Livewire\Admin\Exhibitions;

use App\Models\Exhibition;
use App\Services\QrCodeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithFileUploads;
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

    #[Validate('nullable|url|max:2048')]
    public ?string $redirectUrl = null;

    #[Validate('required|date')]
    public string $startDate = '';

    #[Validate('required|date|after_or_equal:startDate')]
    public string $endDate = '';

    #[Validate('required|string|in:free,paid')]
    public string $entryType = 'free';

    #[Validate('nullable|numeric|min:1|required_if:entryType,paid')]
    public ?string $entryAmount = null;

    #[Validate('nullable|image|max:5120')]
    public $logoUpload = null;

    #[Validate('nullable|image|max:10240')]
    public $passBackgroundUpload = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $passQrX = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $passQrY = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $passQrSize = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $passNameX = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $passNameY = null;

    #[Validate(['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'])]
    public ?string $passNameColor = null;

    #[Validate('nullable|image|max:10240')]
    public $invitationBackgroundUpload = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $invitationStallX = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $invitationStallY = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $invitationCompanyX = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $invitationCompanyY = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $invitationLogoX = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $invitationLogoY = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $invitationLogoSize = null;

    #[Validate(['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'])]
    public ?string $invitationTextColor = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $invitationTextSize = null;

    public ?string $existingLogoUrl = null;

    public ?string $existingPassBackgroundUrl = null;

    public ?string $existingInvitationBackgroundUrl = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetExhibitionForm();
        $this->showAddModal = true;
    }

    public function addExhibition(): void
    {
        $this->validate();

        $exhibition = Exhibition::create([
            'title' => $this->title,
            'description' => $this->description,
            'redirect_url' => $this->redirectUrl ?: null,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'entry_type' => $this->entryType,
            'entry_amount' => $this->entryType === 'paid' ? $this->entryAmount : null,
            'created_by' => Auth::id(),
        ]);

        $this->persistAssets($exhibition);
        $exhibition->save();

        Flux::toast(
            heading: 'Exhibition Added!',
            variant: 'success',
            text: 'Exhibition has been added successfully.'
        );

        $this->showAddModal = false;
        $this->resetExhibitionForm();
    }

    public function openEditModal(int $exhibitionId): void
    {
        $exhibition = Exhibition::findOrFail($exhibitionId);

        $this->resetExhibitionForm();

        $this->exhibitionToEdit = $exhibitionId;
        $this->title = $exhibition->title;
        $this->description = $exhibition->description ?? '';
        $this->redirectUrl = $exhibition->redirect_url;
        $this->startDate = $exhibition->start_date->format('Y-m-d');
        $this->endDate = $exhibition->end_date->format('Y-m-d');
        $this->entryType = $exhibition->entry_type?->value ?? 'free';
        $this->entryAmount = $exhibition->entry_amount ? (string) $exhibition->entry_amount : null;

        $this->existingLogoUrl = $exhibition->logo_url;
        $this->existingPassBackgroundUrl = $exhibition->pass_background_url;
        $this->passQrX = $exhibition->pass_qr_x;
        $this->passQrY = $exhibition->pass_qr_y;
        $this->passQrSize = $exhibition->pass_qr_size;
        $this->passNameX = $exhibition->pass_name_x;
        $this->passNameY = $exhibition->pass_name_y;
        $this->passNameColor = $exhibition->pass_name_color;

        $this->existingInvitationBackgroundUrl = $exhibition->invitation_background_url;
        $this->invitationStallX = $exhibition->invitation_stall_x;
        $this->invitationStallY = $exhibition->invitation_stall_y;
        $this->invitationCompanyX = $exhibition->invitation_company_x;
        $this->invitationCompanyY = $exhibition->invitation_company_y;
        $this->invitationLogoX = $exhibition->invitation_logo_x;
        $this->invitationLogoY = $exhibition->invitation_logo_y;
        $this->invitationLogoSize = $exhibition->invitation_logo_size;
        $this->invitationTextColor = $exhibition->invitation_text_color;
        $this->invitationTextSize = $exhibition->invitation_text_size;

        $this->showEditModal = true;
    }

    public function updateExhibition(): void
    {
        $this->validate();

        $exhibition = Exhibition::findOrFail($this->exhibitionToEdit);

        $exhibition->fill([
            'title' => $this->title,
            'description' => $this->description,
            'redirect_url' => $this->redirectUrl ?: null,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'entry_type' => $this->entryType,
            'entry_amount' => $this->entryType === 'paid' ? $this->entryAmount : null,
        ]);

        $this->persistAssets($exhibition);
        $exhibition->save();

        Flux::toast(
            heading: 'Exhibition Updated!',
            variant: 'success',
            text: 'Exhibition has been updated successfully.'
        );

        $this->showEditModal = false;
        $this->resetExhibitionForm();
    }

    public function removeLogo(): void
    {
        if (! $this->exhibitionToEdit) {
            $this->logoUpload = null;

            return;
        }

        $exhibition = Exhibition::findOrFail($this->exhibitionToEdit);

        if ($exhibition->logo_path) {
            Storage::disk('public')->delete($exhibition->logo_path);
            $exhibition->update(['logo_path' => null]);
        }

        $this->existingLogoUrl = null;
        $this->logoUpload = null;
    }

    public function removePassBackground(): void
    {
        if ($this->exhibitionToEdit) {
            $exhibition = Exhibition::findOrFail($this->exhibitionToEdit);

            if ($exhibition->pass_background_path) {
                Storage::disk('public')->delete($exhibition->pass_background_path);
                $exhibition->update([
                    'pass_background_path' => null,
                    'pass_qr_x' => null,
                    'pass_qr_y' => null,
                    'pass_qr_size' => null,
                    'pass_name_x' => null,
                    'pass_name_y' => null,
                    'pass_name_color' => null,
                ]);
            }
        }

        $this->existingPassBackgroundUrl = null;
        $this->passBackgroundUpload = null;
        $this->passQrX = null;
        $this->passQrY = null;
        $this->passQrSize = null;
        $this->passNameX = null;
        $this->passNameY = null;
        $this->passNameColor = null;
    }

    public function removeInvitationBackground(): void
    {
        if ($this->exhibitionToEdit) {
            $exhibition = Exhibition::findOrFail($this->exhibitionToEdit);

            if ($exhibition->invitation_background_path) {
                Storage::disk('public')->delete($exhibition->invitation_background_path);
                $exhibition->update([
                    'invitation_background_path' => null,
                    'invitation_stall_x' => null,
                    'invitation_stall_y' => null,
                    'invitation_company_x' => null,
                    'invitation_company_y' => null,
                    'invitation_logo_x' => null,
                    'invitation_logo_y' => null,
                    'invitation_logo_size' => null,
                    'invitation_text_color' => null,
                    'invitation_text_size' => null,
                ]);
            }
        }

        $this->existingInvitationBackgroundUrl = null;
        $this->invitationBackgroundUpload = null;
        $this->invitationStallX = null;
        $this->invitationStallY = null;
        $this->invitationCompanyX = null;
        $this->invitationCompanyY = null;
        $this->invitationLogoX = null;
        $this->invitationLogoY = null;
        $this->invitationLogoSize = null;
        $this->invitationTextColor = null;
        $this->invitationTextSize = null;
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

        if ($exhibition->logo_path) {
            Storage::disk('public')->delete($exhibition->logo_path);
        }
        if ($exhibition->pass_background_path) {
            Storage::disk('public')->delete($exhibition->pass_background_path);
        }
        if ($exhibition->invitation_background_path) {
            Storage::disk('public')->delete($exhibition->invitation_background_path);
        }

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

    private function persistAssets(Exhibition $exhibition): void
    {
        if ($this->logoUpload instanceof TemporaryUploadedFile) {
            if ($exhibition->logo_path) {
                Storage::disk('public')->delete($exhibition->logo_path);
            }
            $path = $this->logoUpload->store("exhibitions/{$exhibition->id}", 'public');
            $exhibition->logo_path = $path;
        }

        if ($this->passBackgroundUpload instanceof TemporaryUploadedFile) {
            if ($exhibition->pass_background_path) {
                Storage::disk('public')->delete($exhibition->pass_background_path);
            }
            $path = $this->passBackgroundUpload->store("exhibitions/{$exhibition->id}", 'public');
            $exhibition->pass_background_path = $path;
        }

        if ($this->invitationBackgroundUpload instanceof TemporaryUploadedFile) {
            if ($exhibition->invitation_background_path) {
                Storage::disk('public')->delete($exhibition->invitation_background_path);
            }
            $path = $this->invitationBackgroundUpload->store("exhibitions/{$exhibition->id}", 'public');
            $exhibition->invitation_background_path = $path;
        }

        $exhibition->pass_qr_x = $this->passQrX;
        $exhibition->pass_qr_y = $this->passQrY;
        $exhibition->pass_qr_size = $this->passQrSize;
        $exhibition->pass_name_x = $this->passNameX;
        $exhibition->pass_name_y = $this->passNameY;
        $exhibition->pass_name_color = $this->passNameColor;

        $exhibition->invitation_stall_x = $this->invitationStallX;
        $exhibition->invitation_stall_y = $this->invitationStallY;
        $exhibition->invitation_company_x = $this->invitationCompanyX;
        $exhibition->invitation_company_y = $this->invitationCompanyY;
        $exhibition->invitation_logo_x = $this->invitationLogoX;
        $exhibition->invitation_logo_y = $this->invitationLogoY;
        $exhibition->invitation_logo_size = $this->invitationLogoSize;
        $exhibition->invitation_text_color = $this->invitationTextColor;
        $exhibition->invitation_text_size = $this->invitationTextSize;
    }

    private function resetExhibitionForm(): void
    {
        $this->reset([
            'exhibitionToEdit',
            'title',
            'description',
            'redirectUrl',
            'startDate',
            'endDate',
            'entryType',
            'entryAmount',
            'logoUpload',
            'passBackgroundUpload',
            'passQrX',
            'passQrY',
            'passQrSize',
            'passNameX',
            'passNameY',
            'passNameColor',
            'invitationBackgroundUpload',
            'invitationStallX',
            'invitationStallY',
            'invitationCompanyX',
            'invitationCompanyY',
            'invitationLogoX',
            'invitationLogoY',
            'invitationLogoSize',
            'invitationTextColor',
            'invitationTextSize',
            'existingLogoUrl',
            'existingPassBackgroundUrl',
            'existingInvitationBackgroundUrl',
        ]);

        $this->entryType = 'free';
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
