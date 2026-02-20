<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $template,
        public string $phoneCode,
        public string $phoneNumber,
        public array $variables = [],
        public ?string $templateId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! config('services.sms.enabled')) {
            return;
        }

        $sms = SmsService::fromConfig();

        // Format phone number: combine code and number, remove '+' and spaces
        $destination = str_replace(['+', ' '], '', $this->phoneCode.$this->phoneNumber);

        // If template ID is provided, use DLT template sending
        if ($this->templateId) {
            $sms->sendWithTemplateId(
                phoneNumber: $destination,
                templateId: $this->templateId,
                variables: $this->variables
            );
        } else {
            // Fallback to old method for backward compatibility
            $sms->sendTemplate(
                template: $this->template,
                phoneNumber: $destination,
                variables: $this->variables
            );
        }
    }
}
