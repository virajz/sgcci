<?php

namespace App\Livewire\Exhibitions;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking as BookingModel;
use App\Models\Exhibition;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.front')]
class Booking extends Component
{
    #[Locked]
    public int $exhibitionId;

    public string $brandName = '';

    public string $faciaName = '';

    public string $trophyName = '';

    public string $companyProfile = '';

    public array $companyLogo = [];

    public string $contactPerson = '';

    public string $designation = '';

    public string $phoneCode = '+91';

    public string $phoneNumber = '';

    public string $email = '';

    public string $website = '';

    public string $city = 'Surat';

    public string $customCity = '';

    public ?string $gstNumber = null;

    public array $productProfile = [];

    public bool $hasExhibitedBefore = false;

    public array $participationYears = [];

    public bool $isSgcciMember = false;

    public string $membershipType = '';

    public string $membershipNumber = '';

    public string $spaceType = 'standard';

    public bool $agreeToTerms = false;

    public function getExhibitionProperty(): Exhibition
    {
        return Exhibition::findOrFail($this->exhibitionId);
    }

    public array $selectedStalls = [];

    public function getPricingProperty(): array
    {
        if (empty($this->selectedStalls)) {
            $defaultPricePerSqm = match ($this->spaceType) {
                'raw' => 4500,
                'standard' => 5000,
                default => 5000,
            };

            return [
                'total_area' => 0,
                'price_per_sqm' => $defaultPricePerSqm,
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
            $this->membershipType,
            $this->spaceType
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

    public function updatedSpaceType(): void
    {
        // Force pricing recalculation when space type changes
        unset($this->pricing);
    }

    public function handleLogoUpload(array $data): void
    {
        $path = $data['path'] ?? null;
        $filename = $data['filename'] ?? null;
        $url = $data['url'] ?? null;

        if ($path && $filename) {
            $this->companyLogo = [
                'path' => $path,
                'filename' => $filename,
                'url' => $url,
            ];
        }
    }

    public function removeLogo(): void
    {
        if (! empty($this->companyLogo)) {
            $path = is_array($this->companyLogo) ? $this->companyLogo['path'] : $this->companyLogo;

            // Delete the file from storage
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $this->companyLogo = [];
        }
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreBookingRequest)->rules());

        // Use custom city if "Others" is selected
        $cityToSave = $this->city === 'Others' ? $this->customCity : $this->city;

        // Generate a unique token to prevent duplicate submissions
        $bookingToken = \Illuminate\Support\Str::uuid()->toString();

        // Store booking data in session for confirmation page
        session(['booking_data' => [
            'token' => $bookingToken,
            'exhibitionId' => $this->exhibition->id,
            'brandName' => $this->brandName,
            'faciaName' => $this->faciaName,
            'trophyName' => $this->trophyName,
            'companyProfile' => $this->companyProfile,
            'companyLogo' => $this->companyLogo,
            'contactPerson' => $this->contactPerson,
            'designation' => $this->designation,
            'phoneCode' => $this->phoneCode,
            'phoneNumber' => $this->phoneNumber,
            'email' => $this->email,
            'website' => $this->website,
            'city' => $cityToSave,
            'customCity' => $this->customCity,
            'gstNumber' => $this->gstNumber,
            'productProfile' => $this->productProfile,
            'hasExhibitedBefore' => $this->hasExhibitedBefore,
            'participationYears' => $this->participationYears,
            'isSgcciMember' => $this->isSgcciMember,
            'membershipType' => $this->membershipType,
            'membershipNumber' => $this->membershipNumber,
            'spaceType' => $this->spaceType,
            'selectedStalls' => $this->selectedStalls,
        ]]);

        // Redirect to confirmation page
        $this->redirect(route('exhibitions.booking.confirmation', ['exhibition' => $this->exhibition]), navigate: true);
    }

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibitionId = $exhibition->id;

        // Get product profiles from session (set from product profile selection page)
        $productProfiles = session('product_profiles', []);

        // Redirect to product profile selection if not set
        if (empty($productProfiles)) {
            $this->redirect(route('exhibitions.product-profile.select', $exhibition), navigate: true);

            return;
        }

        // Set product profiles from session
        $this->productProfile = $productProfiles;

        // Restore data from session if available (user went back from confirmation)
        $sessionData = session('booking_data', []);
        if (! empty($sessionData)) {
            $this->brandName = $sessionData['brandName'] ?? '';
            $this->faciaName = $sessionData['faciaName'] ?? '';
            $this->trophyName = $sessionData['trophyName'] ?? '';
            $this->companyProfile = $sessionData['companyProfile'] ?? '';
            $this->companyLogo = $sessionData['companyLogo'] ?? [];
            $this->contactPerson = $sessionData['contactPerson'] ?? '';
            $this->designation = $sessionData['designation'] ?? '';
            $this->phoneCode = $sessionData['phoneCode'] ?? '+91';
            $this->phoneNumber = $sessionData['phoneNumber'] ?? '';
            $this->email = $sessionData['email'] ?? '';
            $this->website = $sessionData['website'] ?? '';
            $this->city = $sessionData['city'] ?? 'Surat';
            $this->customCity = $sessionData['customCity'] ?? '';
            $this->gstNumber = $sessionData['gstNumber'] ?? null;
            $this->productProfile = $sessionData['productProfile'] ?? $this->productProfile;
            $this->hasExhibitedBefore = $sessionData['hasExhibitedBefore'] ?? false;
            $this->participationYears = $sessionData['participationYears'] ?? [];
            $this->isSgcciMember = $sessionData['isSgcciMember'] ?? false;
            $this->membershipType = $sessionData['membershipType'] ?? '';
            $this->spaceType = $sessionData['spaceType'] ?? 'standard';
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
