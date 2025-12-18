<?php

namespace App\Livewire\Exhibitions;

use App\Jobs\SendSmsMessage;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking as BookingModel;
use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.front')]
class Confirmation extends Component
{
    #[Locked]
    public int $exhibitionId;

    public array $bookingData = [];

    public function getExhibitionProperty(): Exhibition
    {
        return Exhibition::findOrFail($this->exhibitionId);
    }

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibitionId = $exhibition->id;

        // Get booking data from session
        $this->bookingData = session('booking_data', []);

        // Redirect back to booking if no data
        if (empty($this->bookingData)) {
            $this->redirect(route('exhibitions.booking.show', $exhibition), navigate: true);
        }
    }

    public function goBack(): void
    {
        $this->redirect(route('exhibitions.booking.show', $this->exhibition), navigate: true);
    }

    public function confirm(): void
    {
        // Check if booking token exists and hasn't been used
        $token = $this->bookingData['token'] ?? null;
        if (! $token) {
            session()->flash('error', 'Invalid booking session. Please try again.');
            $this->redirect(route('exhibitions.booking.show', $this->exhibition), navigate: true);

            return;
        }

        // Check if this token has already been used
        $usedTokens = session('used_booking_tokens', []);
        if (in_array($token, $usedTokens)) {
            session()->flash('error', 'This booking has already been submitted.');
            $this->redirect(route('exhibitions.booking.show', $this->exhibition), navigate: true);

            return;
        }

        // Create the booking
        $booking = BookingModel::create([
            'exhibition_id' => $this->exhibition->id,
            'brand_name' => $this->bookingData['brandName'],
            'facia_name' => $this->bookingData['faciaName'] ?? null,
            'trophy_name' => $this->bookingData['trophyName'] ?? null,
            'company_profile' => $this->bookingData['companyProfile'] ?? null,
            'contact_person' => $this->bookingData['contactPerson'],
            'designation' => $this->bookingData['designation'] ?? null,
            'phone_code' => $this->bookingData['phoneCode'],
            'phone_number' => $this->bookingData['phoneNumber'],
            'email' => $this->bookingData['email'],
            'website' => $this->bookingData['website'] ?? null,
            'city' => $this->bookingData['city'],
            'gst_number' => $this->bookingData['gstNumber'] ?? null,
            'product_profile' => $this->bookingData['productProfile'],
            'has_exhibited_before' => $this->bookingData['hasExhibitedBefore'],
            'participation_years' => $this->bookingData['participationYears'],
            'is_sgcci_member' => $this->bookingData['isSgcciMember'],
            'membership_type' => $this->bookingData['membershipType'],
            'membership_number' => $this->bookingData['membershipNumber'] ?? null,
            'space_type' => $this->bookingData['spaceType'] ?? 'standard',
            'selected_stalls' => $this->bookingData['selectedStalls'],
        ]);

        // Send WhatsApp message for booking confirmation (in background)
        if (config('services.whatsapp.enabled')) {
            SendWhatsAppCampaign::dispatch(
                campaignName: 'booking_received',
                phoneCode: $booking->phone_code,
                phoneNumber: $booking->phone_number,
                templateParams: [
                    $booking->contact_person,                              // {{1}} Contact Person Name
                    $this->exhibition->title,                              // {{2}} Exhibition Title
                    implode(', ', $booking->selected_stalls),              // {{3}} Selected Stalls
                    $booking->booking_code,                                // {{4}} Booking Code
                    number_format($booking->total_area, 0),                // {{5}} Total Area
                    number_format($booking->total_with_gst, 2),            // {{6}} Total Amount with GST
                ]
            );

            // Send WhatsApp notification to staff members
            \App\Jobs\SendStaffWhatsAppNotifications::dispatch(
                booking: $booking,
                campaignName: 'booking_received'
            );
        }

        // Send SMS message for booking confirmation (in background)
        if (config('services.sms.enabled')) {
            SendSmsMessage::dispatch(
                template: 'booking_received',
                phoneCode: $booking->phone_code,
                phoneNumber: $booking->phone_number,
                variables: [
                    'contact_name' => explode(' ', trim($booking->contact_person))[0],
                    'exhibition' => $this->abbreviateTitle($this->exhibition->title),
                    'booking_code' => $booking->booking_code,
                ]
            );
        }

        // Mark token as used
        $usedTokens[] = $token;
        session(['used_booking_tokens' => $usedTokens]);

        // Clear booking data
        session()->forget('booking_data');

        // Redirect to thank you page
        $this->redirect(route('exhibitions.booking.thank-you', ['exhibition' => $this->exhibition, 'bookingCode' => $booking->booking_code]), navigate: true);
    }

    public function getLineItemsProperty(): array
    {
        if (empty($this->bookingData['selectedStalls'])) {
            return [];
        }

        return BookingModel::getStallLineItems(
            $this->bookingData['selectedStalls'],
            $this->bookingData['spaceType'] ?? 'standard'
        );
    }

    public function getPricingProperty(): array
    {
        if (empty($this->bookingData['selectedStalls'])) {
            $spaceType = $this->bookingData['spaceType'] ?? 'standard';
            $defaultPricePerSqm = match ($spaceType) {
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
            $this->bookingData['selectedStalls'],
            $this->bookingData['hasExhibitedBefore'] ?? false,
            $this->bookingData['participationYears'] ?? [],
            $this->bookingData['isSgcciMember'] ?? false,
            $this->bookingData['membershipType'] ?? null,
            $this->bookingData['spaceType'] ?? 'standard'
        );
    }

    /**
     * Abbreviate exhibition title for SMS.
     */
    private function abbreviateTitle(string $title, int $maxLength = 20): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        return substr($title, 0, $maxLength - 3).'...';
    }

    public function render()
    {
        return view('livewire.exhibitions.confirmation');
    }
}
