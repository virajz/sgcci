<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitor;

use App\Models\Booking;
use App\Models\Exhibition;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class InvitationPass extends Component
{
    use WithFileUploads;

    public ?Booking $booking = null;

    public ?Exhibition $exhibition = null;

    #[Validate('nullable|image|max:5120')]
    public $logoUpload = null;

    #[Validate('required|string|max:100')]
    public string $stallNo = '';

    #[Validate('required|string|max:255')]
    public string $companyName = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user->isExhibitor()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $user->booking;

        if (! $this->booking) {
            abort(404, 'No booking found for this exhibitor.');
        }

        $this->exhibition = $this->booking->exhibition;

        $this->stallNo = $this->booking->invitation_stall_no
            ?? implode(', ', $this->booking->selected_stalls ?? []);
        $this->companyName = $this->booking->invitation_company_name
            ?? ($this->booking->brand_name ?? '');
    }

    public function save(): void
    {
        $this->validate();

        $logoPath = $this->booking->invitation_logo_path;

        if ($this->logoUpload instanceof TemporaryUploadedFile) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $this->logoUpload->store("exhibitions/{$this->booking->exhibition_id}/invitation-logos", 'public');
        }

        $this->booking->update([
            'invitation_logo_path' => $logoPath,
            'invitation_stall_no' => $this->stallNo,
            'invitation_company_name' => $this->companyName,
        ]);

        $this->booking->refresh();
        $this->logoUpload = null;

        Flux::toast(
            heading: 'Saved',
            variant: 'success',
            text: 'Your invitation pass details have been saved.',
        );

        $this->dispatch('invitation-pass-updated');
    }

    public function removeLogo(): void
    {
        if ($this->booking->invitation_logo_path) {
            Storage::disk('public')->delete($this->booking->invitation_logo_path);
            $this->booking->update(['invitation_logo_path' => null]);
            $this->booking->refresh();
        }

        $this->logoUpload = null;

        $this->dispatch('invitation-pass-updated');
    }

    public function hasSavedDetails(): bool
    {
        return $this->booking->invitation_logo_path !== null
            || ! empty($this->booking->invitation_stall_no)
            || ! empty($this->booking->invitation_company_name);
    }

    public function render()
    {
        return view('livewire.exhibitor.invitation-pass');
    }
}
