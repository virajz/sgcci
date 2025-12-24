<?php

namespace App\Livewire\Admin\Inquiries;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EditBooking extends Component
{
    public Booking $booking;

    public string $brandName = '';

    public string $faciaName = '';

    public string $trophyName = '';

    public string $contactPerson = '';

    public string $phoneCode = '+91';

    public string $phoneNumber = '';

    public string $email = '';

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

    public string $stallsInput = '';

    public array $selectedStalls = [];

    public float $totalArea = 0;

    public float $pricePerSqm = 0;

    public float $totalPrice = 0;

    public float $discountPercentage = 0;

    public float $discountAmount = 0;

    public float $priceAfterDiscount = 0;

    public float $gstAmount = 0;

    public float $totalWithGst = 0;

    public function mount(Booking $booking): void
    {
        // Ensure user has admin privileges
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if payment has been received
        if ($booking->amount_paid > 0) {
            Flux::toast(
                heading: 'Cannot Edit Booking',
                variant: 'danger',
                text: 'This booking cannot be edited as payment has already been received.'
            );

            $this->redirect(route('admin.inquiries.show', $booking), navigate: true);

            return;
        }

        $this->booking = $booking;

        // Populate form fields
        $this->brandName = $booking->brand_name;
        $this->faciaName = $booking->facia_name ?? '';
        $this->trophyName = $booking->trophy_name ?? '';
        $this->contactPerson = $booking->contact_person;
        $this->phoneCode = $booking->phone_code;
        $this->phoneNumber = $booking->phone_number;
        $this->email = $booking->email;
        $this->city = $booking->city;
        $this->gstNumber = $booking->gst_number;
        $this->productProfile = $booking->product_profile ?? [];
        $this->hasExhibitedBefore = $booking->has_exhibited_before ?? false;
        $this->participationYears = $booking->participation_years ?? [];
        $this->isSgcciMember = $booking->is_sgcci_member ?? false;
        $this->membershipType = $booking->membership_type ?? '';
        $this->membershipNumber = $booking->membership_number ?? '';
        $this->spaceType = $booking->space_type;

        // Set custom city if needed
        $cities = ['Surat', 'Ahmedabad', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Others'];
        if (! in_array($booking->city, $cities)) {
            $this->city = 'Others';
            $this->customCity = $booking->city;
        }

        // Initialize stall selection
        $this->selectedStalls = $booking->selected_stalls ?? [];
        $this->stallsInput = implode(', ', $this->selectedStalls);

        // Calculate initial pricing
        $this->calculatePricing();
    }

    public function updatedStallsInput(): void
    {
        // Parse the comma-separated input and clean it
        $stalls = array_map('trim', explode(',', $this->stallsInput));
        $stalls = array_filter($stalls, fn ($stall) => ! empty($stall));
        $stalls = array_values(array_unique($stalls));

        $this->selectedStalls = $stalls;

        // Recalculate pricing with new stalls
        $this->calculatePricing();
    }

    public function updatedSpaceType(): void
    {
        $this->calculatePricing();
    }

    public function updatedHasExhibitedBefore(): void
    {
        if (! $this->hasExhibitedBefore) {
            $this->participationYears = [];
        }

        $this->calculatePricing();
    }

    public function updatedParticipationYears(): void
    {
        $this->calculatePricing();
    }

    public function updatedIsSgcciMember(): void
    {
        if (! $this->isSgcciMember) {
            $this->membershipType = '';
            $this->membershipNumber = '';
        }

        $this->calculatePricing();
    }

    public function updatedMembershipType(): void
    {
        $this->calculatePricing();
    }

    protected function calculatePricing(): void
    {
        if (empty($this->selectedStalls)) {
            $this->totalArea = 0;
            $this->pricePerSqm = 0;
            $this->totalPrice = 0;
            $this->discountPercentage = 0;
            $this->discountAmount = 0;
            $this->priceAfterDiscount = 0;
            $this->gstAmount = 0;
            $this->totalWithGst = 0;

            return;
        }

        $pricing = Booking::calculatePricing(
            $this->selectedStalls,
            $this->hasExhibitedBefore,
            $this->participationYears,
            $this->isSgcciMember,
            $this->membershipType,
            $this->spaceType
        );

        $this->totalArea = $pricing['total_area'];
        $this->pricePerSqm = $pricing['price_per_sqm'];
        $this->totalPrice = $pricing['total_price'];
        $this->discountPercentage = $pricing['discount_percentage'];
        $this->discountAmount = $pricing['discount_amount'];
        $this->priceAfterDiscount = $pricing['price_after_discount'];
        $this->gstAmount = $pricing['gst_amount'];
        $this->totalWithGst = $pricing['total_with_gst'];
    }

    protected function rules(): array
    {
        return [
            'brandName' => ['required', 'string', 'max:255'],
            'faciaName' => ['nullable', 'string', 'max:255'],
            'trophyName' => ['nullable', 'string', 'max:255'],
            'contactPerson' => ['required', 'string', 'max:255'],
            'phoneCode' => ['required', 'string', 'max:10'],
            'phoneNumber' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'customCity' => ['required_if:city,Others', 'nullable', 'string', 'max:255'],
            'gstNumber' => ['nullable', 'string', 'max:15'],
            'productProfile' => ['required', 'array', 'min:1'],
            'productProfile.*' => ['string'],
            'hasExhibitedBefore' => ['boolean'],
            'participationYears' => ['nullable', 'array'],
            'participationYears.*' => ['string'],
            'isSgcciMember' => ['boolean'],
            'membershipType' => ['nullable', 'string'],
            'membershipNumber' => ['required_with:membershipType', 'string', 'max:100'],
            'spaceType' => ['required', 'string', 'in:standard,raw'],
            'stallsInput' => ['required', 'string'],
            'selectedStalls' => ['required', 'array', 'min:1'],
            'selectedStalls.*' => ['string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'brandName.required' => 'Please enter your brand or dealership name.',
            'contactPerson.required' => 'Please enter the contact person name.',
            'phoneNumber.required' => 'Please enter a phone number.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'city.required' => 'Please select a city.',
            'customCity.required_if' => 'Please enter your city name.',
            'productProfile.required' => 'Please select at least one product profile.',
            'productProfile.min' => 'Please select at least one product profile.',
            'spaceType.required' => 'Please select a space type.',
            'spaceType.in' => 'Invalid space type selected.',
            'membershipNumber.required_with' => 'Please enter your membership number.',
            'stallsInput.required' => 'Please enter at least one stall number.',
            'selectedStalls.required' => 'Please enter at least one stall number.',
            'selectedStalls.min' => 'Please enter at least one stall number.',
        ];
    }

    public function updateBooking(): void
    {
        // Double check payment status before updating
        if ($this->booking->amount_paid > 0) {
            Flux::toast(
                heading: 'Cannot Edit Booking',
                variant: 'danger',
                text: 'This booking cannot be edited as payment has already been received.'
            );

            $this->redirect(route('admin.inquiries.show', $this->booking), navigate: true);

            return;
        }

        $this->validate();

        // Use custom city if "Others" is selected
        $cityToSave = $this->city === 'Others' ? $this->customCity : $this->city;

        // Update booking data without resetting approval workflow (admin edits don't require re-approval)
        $updateData = [
            'brand_name' => $this->brandName,
            'facia_name' => $this->faciaName,
            'trophy_name' => $this->trophyName,
            'contact_person' => $this->contactPerson,
            'phone_code' => $this->phoneCode,
            'phone_number' => $this->phoneNumber,
            'email' => $this->email,
            'city' => $cityToSave,
            'gst_number' => $this->gstNumber,
            'product_profile' => $this->productProfile,
            'has_exhibited_before' => $this->hasExhibitedBefore,
            'participation_years' => $this->participationYears,
            'is_sgcci_member' => $this->isSgcciMember,
            'membership_type' => $this->membershipType,
            'membership_number' => $this->membershipNumber,
            'space_type' => $this->spaceType,
            'selected_stalls' => $this->selectedStalls,
            'total_area' => $this->totalArea,
            'price_per_sqm' => $this->pricePerSqm,
            'total_price' => $this->totalPrice,
            'discount_percentage' => $this->discountPercentage,
            'discount_amount' => $this->discountAmount,
            'price_after_discount' => $this->priceAfterDiscount,
            'gst_amount' => $this->gstAmount,
            'total_with_gst' => $this->totalWithGst,
        ];

        $this->booking->update($updateData);

        // Send WhatsApp notification to customer about booking update (only for pending approval)
        if (config('services.whatsapp.enabled') && $this->booking->status === \App\BookingStatus::PendingApproval) {
            SendWhatsAppCampaign::dispatch(
                campaignName: 'booking_received',
                phoneCode: $this->booking->phone_code,
                phoneNumber: $this->booking->phone_number,
                templateParams: [
                    $this->booking->contact_person,                              // {{1}} Contact Person Name
                    $this->booking->exhibition->title,                           // {{2}} Exhibition Title
                    implode(', ', $this->booking->selected_stalls),              // {{3}} Selected Stalls
                    $this->booking->booking_code,                                // {{4}} Booking Code
                    number_format($this->booking->total_area, 0),                // {{5}} Total Area
                    number_format($this->booking->total_with_gst, 2),            // {{6}} Total Amount with GST
                ]
            );
        }

        Flux::toast(
            heading: 'Booking Updated!',
            variant: 'success',
            text: 'The booking has been successfully updated.'
        );

        $this->redirect(route('admin.inquiries.show', $this->booking), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.inquiries.edit-booking');
    }
}
