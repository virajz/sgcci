<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function __construct(
        private string $apiKey,
        private string $apiUrl,
        private string $username,
        private string $source,
    ) {}

    /**
     * Send a WhatsApp campaign message.
     */
    public function sendCampaign(
        string $campaignName,
        string $destination,
        array $templateParams = [],
        array $paramsFallbackValue = [],
        array $media = [],
        array $buttons = [],
        array $carouselCards = [],
        array $location = [],
        array $attributes = []
    ): bool {
        try {
            $response = Http::post($this->apiUrl, [
                'apiKey' => $this->apiKey,
                'campaignName' => $campaignName,
                'destination' => $destination,
                'userName' => $this->username,
                'templateParams' => $templateParams,
                'source' => $this->source,
                'media' => $media,
                'buttons' => $buttons,
                'carouselCards' => $carouselCards,
                'location' => $location,
                'attributes' => $attributes,
                'paramsFallbackValue' => $paramsFallbackValue,
            ]);

            if ($response->successful()) {
                Log::channel('whatsapp')->info('WhatsApp campaign sent successfully', [
                    'campaign' => $campaignName,
                    'destination' => $destination,
                ]);

                return true;
            }

            Log::channel('whatsapp')->error('WhatsApp campaign failed', [
                'campaign' => $campaignName,
                'destination' => $destination,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('WhatsApp campaign exception', [
                'campaign' => $campaignName,
                'destination' => $destination,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a new WhatsApp service instance from config.
     */
    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('services.whatsapp.api_key'),
            apiUrl: config('services.whatsapp.api_url'),
            username: config('services.whatsapp.username'),
            source: config('services.whatsapp.source'),
        );
    }
}
