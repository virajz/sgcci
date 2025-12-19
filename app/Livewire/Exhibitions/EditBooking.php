<?php

namespace App\Livewire\Exhibitions;

use App\Http\Requests\UpdateBookingRequest;
use App\Jobs\SendStaffWhatsAppNotifications;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.front')]
class EditBooking extends Component
{
    public ?Booking $booking = null;

    public string $bookingCode = '';

    public bool $bookingFound = false;

    public bool $paymentReceived = false;

    public bool $updateSuccessful = false;

    public string $brandName = '';

    public string $faciaName = '';

    public string $trophyName = '';

    public array $companyLogo = [];

    public string $contactPerson = '';

    public string $designation = '';

    public string $phoneCode = '+91';

    public string $phoneNumber = '';

    public string $email = '';

    public string $website = '';

    public string $address = '';

    public string $billingAddress = '';

    public bool $sameAsBillingAddress = true;

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

    public function findBooking(): void
    {
        // Reset state before searching
        $this->reset(['booking', 'bookingFound', 'paymentReceived']);

        $this->validate([
            'bookingCode' => ['required', 'string', 'size:8'],
        ], [
            'bookingCode.required' => 'Please enter your booking code.',
            'bookingCode.size' => 'Booking code must be exactly 8 characters.',
        ]);

        $booking = Booking::where('booking_code', strtoupper($this->bookingCode))->first();

        if (! $booking) {
            Flux::toast(
                heading: 'Booking Not Found',
                variant: 'danger',
                text: 'No booking found with this code. Please check and try again.'
            );

            return;
        }

        // Check if payment has been received
        if ($booking->amount_paid > 0) {
            $this->booking = $booking;
            $this->bookingFound = true;
            $this->paymentReceived = true;

            return;
        }

        $this->booking = $booking;
        $this->bookingFound = true;

        // Populate form fields
        $this->brandName = $booking->brand_name;
        $this->faciaName = $booking->facia_name ?? '';
        $this->trophyName = $booking->trophy_name ?? '';

        // Set company logo if exists
        if ($booking->company_logo) {
            $this->companyLogo = [
                'path' => $booking->company_logo,
                'filename' => $booking->company_logo_original_name ?? basename($booking->company_logo),
                'url' => Storage::disk('public')->url($booking->company_logo),
            ];
        }

        $this->contactPerson = $booking->contact_person;
        $this->designation = $booking->designation ?? '';
        $this->phoneCode = $booking->phone_code;
        $this->phoneNumber = $booking->phone_number;
        $this->email = $booking->email;
        $this->website = $booking->website ?? '';
        $this->address = $booking->address ?? '';
        $this->billingAddress = $booking->billing_address ?? '';
        $this->sameAsBillingAddress = ($booking->address === $booking->billing_address);
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
    }

    public function updatedSameAsBillingAddress(): void
    {
        // Sync billing address with address when checkbox is checked
        if ($this->sameAsBillingAddress) {
            $this->billingAddress = $this->address;
        }
    }

    public function updatedAddress(): void
    {
        // Auto-sync billing address when same as billing is checked
        if ($this->sameAsBillingAddress) {
            $this->billingAddress = $this->address;
        }
    }

    public function updatedPricing()
    {
        return Booking::calculatePricing(
            $this->booking->selected_stalls ?? [],
            $this->hasExhibitedBefore,
            $this->participationYears,
            $this->isSgcciMember,
            $this->membershipType,
            $this->spaceType
        );
    }

    public function handleLogoUpload(array $data): void
    {
        $path = $data['path'] ?? null;
        $filename = $data['filename'] ?? null;
        $url = $data['url'] ?? null;

        if ($path && $filename) {
            // Delete old logo if exists
            if (! empty($this->companyLogo) && isset($this->companyLogo['path'])) {
                $oldPath = $this->companyLogo['path'];
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

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

    public function updateBooking(): void
    {
        if (! $this->booking) {
            Flux::toast(
                heading: 'Booking Not Found',
                variant: 'danger',
                text: 'Please find your booking first.'
            );

            return;
        }

        // Double check payment status before updating
        if ($this->booking->amount_paid > 0) {
            Flux::toast(
                heading: 'Cannot Edit Booking',
                variant: 'danger',
                text: 'This booking cannot be edited as payment has already been received.'
            );

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

        // Get logo path and original filename
        $logoPath = null;
        $logoOriginalName = null;
        if (! empty($this->companyLogo) && isset($this->companyLogo['path'])) {
            $logoPath = $this->companyLogo['path'];
            $logoOriginalName = $this->companyLogo['filename'] ?? null;
        }

        // Reset approval status if booking was previously approved
        $updateData = [
            'brand_name' => $this->brandName,
            'facia_name' => $this->faciaName,
            'trophy_name' => $this->trophyName,
            'company_logo' => $logoPath,
            'company_logo_original_name' => $logoOriginalName,
            'contact_person' => $this->contactPerson,
            'designation' => $this->designation,
            'phone_code' => $this->phoneCode,
            'phone_number' => $this->phoneNumber,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            'billing_address' => $this->billingAddress,
            'city' => $cityToSave,
            'gst_number' => $this->gstNumber,
            'product_profile' => $this->productProfile,
            'has_exhibited_before' => $this->hasExhibitedBefore,
            'participation_years' => $this->participationYears,
            'is_sgcci_member' => $this->isSgcciMember,
            'membership_type' => $this->membershipType,
            'membership_number' => $this->membershipNumber,
            'space_type' => $this->spaceType,
            'price_per_sqm' => $pricing['price_per_sqm'],
            'total_price' => $pricing['total_price'],
            'discount_percentage' => $pricing['discount_percentage'],
            'discount_amount' => $pricing['discount_amount'],
            'price_after_discount' => $pricing['price_after_discount'],
            'gst_amount' => $pricing['gst_amount'],
            'total_with_gst' => $pricing['total_with_gst'],
        ];

        // Reset approval workflow if booking was already approved
        if ($this->booking->status !== \App\BookingStatus::PendingApproval) {
            $updateData['status'] = \App\BookingStatus::PendingApproval;
            $updateData['admin_approved_by'] = null;
            $updateData['admin_approved_at'] = null;
            $updateData['super_admin_approved_by'] = null;
            $updateData['super_admin_approved_at'] = null;
            $updateData['payment_link'] = null;
            $updateData['payment_link_sent_at'] = null;
            $updateData['payment_due_at'] = null;
        }

        $this->booking->update($updateData);

        // Send WhatsApp message for updated booking (if status was reset to pending approval)
        if (isset($updateData['status']) && $updateData['status'] === \App\BookingStatus::PendingApproval) {
            if (config('services.whatsapp.enabled')) {
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

                // Send WhatsApp notification to staff members
                SendStaffWhatsAppNotifications::dispatch(
                    booking: $this->booking,
                    campaignName: 'booking_received'
                );
            }
        }

        // Mark as successful and refresh
        $this->updateSuccessful = true;
        $this->booking->refresh();
    }

    public function searchAnother(): void
    {
        $this->reset(['booking', 'bookingFound', 'paymentReceived', 'updateSuccessful', 'bookingCode']);
    }

    public function render()
    {
        return view('livewire.exhibitions.edit-booking');
    }
}
