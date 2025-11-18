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
        public array $variables = []
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

        $sms->sendTemplate(
            template: $this->template,
            phoneNumber: $destination,
            variables: $this->variables
        );
    }
}
