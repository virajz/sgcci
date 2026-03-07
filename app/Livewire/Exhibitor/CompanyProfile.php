<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitor;

use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class CompanyProfile extends Component
{
    use WithFileUploads;

    public ?Booking $booking = null;

    public string $messageType = 'text';

    public string $messageText = '';

    public $messageMedia = null;

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

        $this->messageType = $this->booking->profile_message_type ?? 'text';
        $this->messageText = $this->booking->profile_message_text ?? '';
    }

    public function updatedMessageType(): void
    {
        $this->messageMedia = null;
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $mediaPath = $this->booking->profile_message_media;
        $mediaOriginalName = $this->booking->profile_message_media_original_name;

        if ($this->messageMedia) {
            if ($mediaPath) {
                Storage::delete($mediaPath);
            }
            $mediaPath = $this->messageMedia->store('profile-messages');
            $mediaOriginalName = $this->messageMedia->getClientOriginalName();
        }

        $this->booking->update([
            'profile_message_type' => $this->messageType,
            'profile_message_text' => $this->messageText ?: null,
            'profile_message_media' => $mediaPath,
            'profile_message_media_original_name' => $mediaOriginalName,
        ]);

        $this->messageMedia = null;

        Flux::toast(heading: 'Profile Saved', variant: 'success', text: 'Your company profile has been saved.');
    }

    public function removeMedia(): void
    {
        if ($this->booking->profile_message_media) {
            Storage::delete($this->booking->profile_message_media);
        }

        $this->booking->update([
            'profile_message_media' => null,
            'profile_message_media_original_name' => null,
        ]);

        $this->messageMedia = null;
    }

    private function rules(): array
    {
        $rules = [
            'messageType' => ['required', 'in:text,image,video'],
            'messageText' => ['nullable', 'string', 'max:2500'],
        ];

        if ($this->messageType === 'image') {
            $rules['messageMedia'] = ['nullable', 'image', 'max:5120']; // 5MB
        } elseif ($this->messageType === 'video') {
            $rules['messageMedia'] = ['nullable', 'mimes:mp4,mov,avi,webm', 'max:16384']; // 16MB
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.exhibitor.company-profile');
    }
}
