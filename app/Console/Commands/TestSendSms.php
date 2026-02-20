<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSendSms extends Command
{
    protected $signature = 'sms:test {phone} {name} {link}';

    protected $description = 'Test SMS sending with DLT template';

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $name = $this->argument('name');
        $link = $this->argument('link');

        $this->info("Sending SMS to: {$phone}");
        $this->info("Name: {$name}");
        $this->info("Link: {$link}");
        $this->info('Template ID: 1707177157041193630');

        $sms = SmsService::fromConfig();

        $result = $sms->sendWithTemplateId(
            phoneNumber: $phone,
            templateId: '1707177157041193630',
            variables: [$name, $link]
        );

        if ($result) {
            $this->info('✓ SMS sent successfully!');
            $this->info('Check storage/logs/sms-'.now()->format('Y-m-d').'.log for details');

            return self::SUCCESS;
        }

        $this->error('✗ SMS sending failed!');
        $this->error('Check storage/logs/sms-'.now()->format('Y-m-d').'.log for error details');

        return self::FAILURE;
    }
}
