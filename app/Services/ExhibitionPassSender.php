<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;

class ExhibitionPassSender
{
    public function __construct(public Exhibition $exhibition) {}

    /**
     * Register the contact for the exhibition as a confirmed free entry
     * (reusing any existing registration for the same phone number), then
     * dispatch the visitor pass over WhatsApp.
     *
     * Returns false when the contact has no usable name or phone and is skipped.
     */
    public function sendToContact(string $name, ?string $phoneNumber, ?string $company = null, ?string $city = null, string $source = 'admin'): bool
    {
        $name = trim($name);
        $phoneNumber = trim((string) $phoneNumber);

        if ($name === '' || $phoneNumber === '') {
            return false;
        }

        $visitor = ExhibitionVisitor::firstOrNew([
            'exhibition_id' => $this->exhibition->id,
            'phone_number' => $phoneNumber,
        ]);

        if (! $visitor->exists) {
            $visitor->fill([
                'name' => $name,
                'company_name' => $company ?: null,
                'state' => 'Gujarat',
                'city' => $city ?: 'Surat',
                'source' => $source,
            ]);
        }

        $visitor->status = VisitorRegistrationStatus::Confirmed;
        $visitor->save();

        // Advance updated_at so the pass image URL changes on every send,
        // forcing WhatsApp to fetch a fresh image instead of a cached one.
        $visitor->touch();

        if (config('services.whatsapp.enabled')) {
            $this->dispatchPass($visitor);
        }

        return true;
    }

    private function dispatchPass(ExhibitionVisitor $visitor): void
    {
        $exhibitionDates = $this->exhibition->start_date->format('d M Y').' to '.$this->exhibition->end_date->format('d M Y');
        $firstName = explode(' ', trim($visitor->name))[0];
        $imageUrl = config('app.url').'/'.$this->exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image?v='.$visitor->updated_at->timestamp;

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $firstName,                             // {{1}} - Name in title
                $this->exhibition->title,               // {{2}} - Exhibition
                $visitor->registration_code,            // {{3}} - Registration Code
                $firstName,                             // {{4}} - Name in body
                $visitor->company_name ?: 'N/A',        // {{5}} - Company
                $visitor->city,                         // {{6}} - City
                'Free Entry',                           // {{7}} - Amount
                'N/A',                                  // {{8}} - Transaction ID
                $visitor->created_at->format('d-m-Y'),  // {{9}} - Payment Date
                $exhibitionDates,                       // {{10}} - Exhibition Dates
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $imageUrl,
                'filename' => 'visitor_pass_'.$visitor->registration_code,
            ]
        );
    }
}
