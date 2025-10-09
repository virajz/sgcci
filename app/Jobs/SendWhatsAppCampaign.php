<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppCampaign implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $campaignName,
        public string $phoneCode,
        public string $phoneNumber,
        public array $templateParams = [],
        public array $paramsFallbackValue = [],
        public array $media = [],
        public array $buttons = [],
        public array $carouselCards = [],
        public array $location = [],
        public array $attributes = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $whatsapp = WhatsAppService::fromConfig();

        // Format phone number: remove '+' and spaces, combine code and number
        $destination = str_replace(['+', ' '], '', $this->phoneCode.$this->phoneNumber);

        $whatsapp->sendCampaign(
            campaignName: $this->campaignName,
            destination: $destination,
            templateParams: $this->templateParams,
            paramsFallbackValue: $this->paramsFallbackValue,
            media: $this->media,
            buttons: $this->buttons,
            carouselCards: $this->carouselCards,
            location: $this->location,
            attributes: $this->attributes
        );
    }
}
