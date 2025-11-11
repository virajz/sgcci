<?php

namespace App\Livewire\Admin\Inquiries;

use App\Http\Requests\UpdateBookingRequest;
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

    public string $spaceType = 'standard';

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
        $this->spaceType = $booking->space_type;

        // Set custom city if needed
        $cities = ['Surat', 'Ahmedabad', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Others'];
        if (! in_array($booking->city, $cities)) {
            $this->city = 'Others';
            $this->customCity = $booking->city;
        }
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

        $this->validate((new UpdateBookingRequest)->rules());

        // Use custom city if "Others" is selected
        $cityToSave = $this->city === 'Others' ? $this->customCity : $this->city;

        // Recalculate pricing with new values
        $pricing = Booking::calculatePricing(
            $this->booking->selected_stalls,
            $this->hasExhibitedBefore,
            $this->participationYears,
            $this->isSgcciMember,
            $this->membershipType,
            $this->spaceType
        );

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
            'space_type' => $this->spaceType,
            'price_per_sqm' => $pricing['price_per_sqm'],
            'total_price' => $pricing['total_price'],
            'discount_percentage' => $pricing['discount_percentage'],
            'discount_amount' => $pricing['discount_amount'],
            'price_after_discount' => $pricing['price_after_discount'],
            'gst_amount' => $pricing['gst_amount'],
            'total_with_gst' => $pricing['total_with_gst'],
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
