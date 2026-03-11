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

    public string $message2Type = 'text';

    public string $message2Text = '';

    public $message2Media = null;

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
        $this->message2Type = $this->booking->profile_message_2_type ?? 'text';
        $this->message2Text = $this->booking->profile_message_2_text ?? '';
    }

    public function updatedMessageType(): void
    {
        $this->messageMedia = null;
    }

    public function updatedMessage2Type(): void
    {
        $this->message2Media = null;
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

        $media2Path = $this->booking->profile_message_2_media;
        $media2OriginalName = $this->booking->profile_message_2_media_original_name;

        if ($this->message2Media) {
            if ($media2Path) {
                Storage::delete($media2Path);
            }
            $media2Path = $this->message2Media->store('profile-messages');
            $media2OriginalName = $this->message2Media->getClientOriginalName();
        }

        $this->booking->update([
            'profile_message_type' => $this->messageType,
            'profile_message_text' => $this->messageText ?: null,
            'profile_message_media' => $mediaPath,
            'profile_message_media_original_name' => $mediaOriginalName,
            'profile_message_2_type' => $this->message2Type,
            'profile_message_2_text' => $this->message2Text ?: null,
            'profile_message_2_media' => $media2Path,
            'profile_message_2_media_original_name' => $media2OriginalName,
        ]);

        $this->messageMedia = null;
        $this->message2Media = null;

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

    public function removeMedia2(): void
    {
        if ($this->booking->profile_message_2_media) {
            Storage::delete($this->booking->profile_message_2_media);
        }

        $this->booking->update([
            'profile_message_2_media' => null,
            'profile_message_2_media_original_name' => null,
        ]);

        $this->message2Media = null;
    }

    private function rules(): array
    {
        $rules = [
            'messageType' => ['required', 'in:text,image,video,pdf'],
            'messageText' => ['nullable', 'string', 'max:2500'],
            'message2Type' => ['required', 'in:text,image,video,pdf'],
            'message2Text' => ['nullable', 'string', 'max:2500'],
        ];

        if ($this->messageType === 'image') {
            $rules['messageMedia'] = ['nullable', 'image', 'max:5120']; // 5MB
        } elseif ($this->messageType === 'video') {
            $rules['messageMedia'] = ['nullable', 'mimes:mp4,mov,avi,webm', 'max:16384']; // 16MB
        } elseif ($this->messageType === 'pdf') {
            $rules['messageMedia'] = ['nullable', 'mimes:pdf', 'max:16384']; // 16MB
        }

        if ($this->message2Type === 'image') {
            $rules['message2Media'] = ['nullable', 'image', 'max:5120']; // 5MB
        } elseif ($this->message2Type === 'video') {
            $rules['message2Media'] = ['nullable', 'mimes:mp4,mov,avi,webm', 'max:16384']; // 16MB
        } elseif ($this->message2Type === 'pdf') {
            $rules['message2Media'] = ['nullable', 'mimes:pdf', 'max:16384']; // 16MB
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.exhibitor.company-profile');
    }
}
