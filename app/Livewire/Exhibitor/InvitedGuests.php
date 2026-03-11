<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitor;

use App\Jobs\SendWhatsAppCampaign;
use App\Livewire\Exhibitions\VisitorsRegistration;
use App\Models\Booking;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class InvitedGuests extends Component
{
    public ?Booking $booking = null;

    public bool $showAddModal = false;

    public bool $showDeleteModal = false;

    public ?int $deletingGuestId = null;

    public string $deletingGuestName = '';

    public string $phoneNumber = '';

    public string $name = '';

    public string $companyName = '';

    public string $designation = '';

    public string $state = '';

    public string $city = '';

    public string $email = '';

    public string $segment = '';

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

        if ($this->booking->invited_guests_limit <= 0) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function updatedState(): void
    {
        $this->city = '';
    }

    /**
     * @return array<string>
     */
    #[Computed]
    public function cities(): array
    {
        $map = VisitorsRegistration::getStateCityMap();

        return $map[$this->state] ?? [];
    }

    public function openAddModal(): void
    {
        $this->resetGuestForm();
        $this->showAddModal = true;
    }

    public function addGuest(): void
    {
        $this->validate([
            'phoneNumber' => [
                'required',
                'string',
                'max:20',
                Rule::unique('exhibition_visitors', 'phone_number')
                    ->where('exhibition_id', $this->booking->exhibition_id)
                    ->whereIn('status', [VisitorRegistrationStatus::Confirmed->value]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'state' => ['required', 'string', 'in:'.implode(',', array_keys(VisitorsRegistration::getStateCityMap()))],
            'city' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'segment' => ['nullable', 'string', 'in:Business,Job (Working Professional),Student,Housewife,Other'],
        ], [
            'phoneNumber.required' => 'Please enter the guest\'s phone number.',
            'phoneNumber.unique' => 'This phone number is already registered for this exhibition.',
            'name.required' => 'Please enter the guest\'s name.',
            'state.required' => 'Please select a state.',
            'city.required' => 'Please select a city.',
        ]);

        $guestCount = $this->booking->invitedGuests()
            ->where('exhibition_id', $this->booking->exhibition_id)
            ->count();

        if ($guestCount >= $this->booking->invited_guests_limit) {
            $this->addError('phoneNumber', 'Invited guest limit of '.$this->booking->invited_guests_limit.' reached.');

            return;
        }

        $guest = ExhibitionVisitor::create([
            'registration_code' => ExhibitionVisitor::generateUniqueInvitedGuestCode(),
            'exhibition_id' => $this->booking->exhibition_id,
            'invited_by_booking_id' => $this->booking->id,
            'phone_number' => $this->phoneNumber,
            'name' => $this->name,
            'company_name' => $this->companyName ?: null,
            'designation' => $this->designation ?: null,
            'state' => $this->state,
            'city' => $this->city,
            'email' => $this->email ?: null,
            'business_segment' => $this->segment ?: null,
            'sub_business_segment' => null,
            'source' => 'invited_guest',
            'status' => VisitorRegistrationStatus::Confirmed,
        ]);

        if (config('services.whatsapp.enabled')) {
            $this->sendWhatsAppNotification($guest);
        }

        $this->showAddModal = false;
        $this->resetGuestForm();

        Flux::toast(heading: 'Guest Added!', variant: 'success', text: "{$guest->name} has been added and will receive a WhatsApp confirmation.");
    }

    public function deleteGuest(): void
    {
        $guest = $this->booking->invitedGuests()->findOrFail($this->deletingGuestId);

        $guest->delete();

        $this->deletingGuestId = null;
        $this->deletingGuestName = '';
        $this->showDeleteModal = false;

        Flux::toast(heading: 'Guest Removed', variant: 'success', text: 'The invited guest has been removed.');
    }

    private function resetGuestForm(): void
    {
        $this->phoneNumber = '';
        $this->name = '';
        $this->companyName = '';
        $this->designation = '';
        $this->state = '';
        $this->city = '';
        $this->email = '';
        $this->segment = '';
    }

    private function sendWhatsAppNotification(ExhibitionVisitor $guest): void
    {
        $exhibition = $this->booking->exhibition;
        $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');
        $firstName = explode(' ', trim($guest->name))[0];
        $imageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$guest->registration_code.'/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $guest->phone_number,
            templateParams: [
                $firstName,                                     // {{1}} - Name in title
                $exhibition->title,                            // {{2}} - Exhibition
                $guest->registration_code,                     // {{3}} - Registration Code
                $firstName,                                    // {{4}} - Name in body
                $guest->company_name ?: 'N/A',                 // {{5}} - Company
                $guest->city,                                  // {{6}} - City
                'Free Entry',                                  // {{7}} - Amount
                'N/A',                                         // {{8}} - Transaction ID
                $guest->created_at->format('d-m-Y'),           // {{9}} - Payment Date
                $exhibitionDates,                              // {{10}} - Exhibition Dates
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $imageUrl,
                'filename' => 'visitor_pass_'.$guest->registration_code,
            ]
        );
    }

    public function render()
    {
        $guests = $this->booking->invitedGuests()
            ->where('exhibition_id', $this->booking->exhibition_id)
            ->latest()
            ->get();

        return view('livewire.exhibitor.invited-guests', [
            'guests' => $guests,
            'guestCount' => $guests->count(),
            'states' => array_keys(VisitorsRegistration::getStateCityMap()),
        ]);
    }
}
