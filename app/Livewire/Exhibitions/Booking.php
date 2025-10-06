<?php

namespace App\Livewire\Exhibitions;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking as BookingModel;
use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.front')]
class Booking extends Component
{
    public Exhibition $exhibition;

    public string $brandName = '';

    public string $contactPerson = '';

    public string $phoneCode = '+91';

    public string $phoneNumber = '';

    public string $email = '';

    public string $city = 'Surat';

    public array $productProfile = [];

    public bool $hasExhibitedBefore = false;

    public array $participationYears = [];

    public bool $isSgcciMember = false;

    public string $membershipType = '';

    public array $selectedStalls = [];

    #[On('stalls-selected')]
    public function updateSelectedStalls(array $selectedStalls): void
    {
        $this->selectedStalls = $selectedStalls;
    }

    public function clearSelectedStalls(): void
    {
        $this->selectedStalls = [];
        $this->dispatch('clear-stalls');
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreBookingRequest)->rules());

        $booking = BookingModel::create([
            'exhibition_id' => $this->exhibition->id,
            'brand_name' => $this->brandName,
            'contact_person' => $this->contactPerson,
            'phone_code' => $this->phoneCode,
            'phone_number' => $this->phoneNumber,
            'email' => $this->email,
            'city' => $this->city,
            'product_profile' => $this->productProfile,
            'has_exhibited_before' => $this->hasExhibitedBefore,
            'participation_years' => $this->participationYears,
            'is_sgcci_member' => $this->isSgcciMember,
            'membership_type' => $this->membershipType,
            'selected_stalls' => $this->selectedStalls,
        ]);

        $this->redirect(route('exhibitions.booking.thank-you', ['exhibition' => $this->exhibition, 'bookingCode' => $booking->booking_code]), navigate: true);
    }

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibition = $exhibition;
    }

    public function render()
    {
        return view('livewire.exhibitions.booking', [
            'exhibition' => $this->exhibition,
        ]);
    }
}
