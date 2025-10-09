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

    public ?string $gstNumber = null;

    public array $productProfile = [];

    public bool $hasExhibitedBefore = false;

    public array $participationYears = [];

    public bool $isSgcciMember = false;

    public string $membershipType = '';

    public array $selectedStalls = [];

    public function getPricingProperty(): array
    {
        if (empty($this->selectedStalls)) {
            return [
                'total_area' => 0,
                'price_per_sqm' => 750,
                'total_price' => 0,
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'price_after_discount' => 0,
                'gst_amount' => 0,
                'total_with_gst' => 0,
            ];
        }

        return BookingModel::calculatePricing(
            $this->selectedStalls,
            $this->hasExhibitedBefore,
            $this->participationYears,
            $this->isSgcciMember,
            $this->membershipType
        );
    }

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

    public function updatedParticipationYears(): void
    {
        // Force pricing recalculation when participation years change
        unset($this->pricing);
    }

    public function updatedMembershipType(): void
    {
        // Force pricing recalculation when membership type changes
        unset($this->pricing);
    }

    public function updatedHasExhibitedBefore(): void
    {
        // Force pricing recalculation when exhibited before status changes
        unset($this->pricing);
    }

    public function updatedIsSgcciMember(): void
    {
        // Force pricing recalculation when SGCCI membership status changes
        unset($this->pricing);
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreBookingRequest)->rules());

        // Generate a unique token to prevent duplicate submissions
        $bookingToken = \Illuminate\Support\Str::uuid()->toString();

        // Store booking data in session for confirmation page
        session(['booking_data' => [
            'token' => $bookingToken,
            'exhibitionId' => $this->exhibition->id,
            'brandName' => $this->brandName,
            'contactPerson' => $this->contactPerson,
            'phoneCode' => $this->phoneCode,
            'phoneNumber' => $this->phoneNumber,
            'email' => $this->email,
            'city' => $this->city,
            'gstNumber' => $this->gstNumber,
            'productProfile' => $this->productProfile,
            'hasExhibitedBefore' => $this->hasExhibitedBefore,
            'participationYears' => $this->participationYears,
            'isSgcciMember' => $this->isSgcciMember,
            'membershipType' => $this->membershipType,
            'selectedStalls' => $this->selectedStalls,
        ]]);

        // Redirect to confirmation page
        $this->redirect(route('exhibitions.booking.confirmation', ['exhibition' => $this->exhibition]), navigate: true);
    }

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibition = $exhibition;

        // Restore data from session if available (user went back from confirmation)
        $sessionData = session('booking_data', []);
        if (! empty($sessionData)) {
            $this->brandName = $sessionData['brandName'] ?? '';
            $this->contactPerson = $sessionData['contactPerson'] ?? '';
            $this->phoneCode = $sessionData['phoneCode'] ?? '+91';
            $this->phoneNumber = $sessionData['phoneNumber'] ?? '';
            $this->email = $sessionData['email'] ?? '';
            $this->city = $sessionData['city'] ?? 'Surat';
            $this->gstNumber = $sessionData['gstNumber'] ?? null;
            $this->productProfile = $sessionData['productProfile'] ?? [];
            $this->hasExhibitedBefore = $sessionData['hasExhibitedBefore'] ?? false;
            $this->participationYears = $sessionData['participationYears'] ?? [];
            $this->isSgcciMember = $sessionData['isSgcciMember'] ?? false;
            $this->membershipType = $sessionData['membershipType'] ?? '';
            $this->selectedStalls = $sessionData['selectedStalls'] ?? [];
        }
    }

    public function render()
    {
        return view('livewire.exhibitions.booking', [
            'exhibition' => $this->exhibition,
        ]);
    }
}
