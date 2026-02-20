<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSmsTemplates extends Command
{
    protected $signature = 'sms:test-templates {phone}';

    protected $description = 'Test 3 SMS templates on a phone number';

    public function handle(): int
    {
        $phone = $this->argument('phone');

        $this->info('=== Testing 3 SMS Templates on '.$phone.' ===');
        $this->newLine();

        // Test 1: Booking template (DCS=0, route=17)
        $this->info('📱 Test 1: Booking Confirmation (DCS=0, route=17)...');
        $result1 = $this->sendSms([
            'user' => 'ADMSGCCI',
            'password' => 'Sgcci@25',
            'senderid' => 'CHAMBR',
            'channel' => 'Trans',
            'DCS' => 0,
            'flashsms' => 0,
            'number' => $phone,
            'text' => 'Dear 11 Your booking for 11 is CONFIRMED. Booking No: 11 Pay by 11: 11 Team SGCCI',
            'route' => 17,
        ]);
        $this->line('   Result: '.($result1['success'] ? '✅ SUCCESS' : '❌ FAILED - '.$result1['error']));
        $this->newLine();

        // Test 2: OTP template (DCS=0, route=17)
        $this->info('📱 Test 2: OTP Verification (DCS=0, route=17)...');
        $result2 = $this->sendSms([
            'user' => 'ADMSGCCI',
            'password' => 'Sgcci@25',
            'senderid' => 'CHAMBR',
            'channel' => 'Trans',
            'DCS' => 0,
            'flashsms' => 0,
            'number' => $phone,
            'text' => 'Your OTP to verify your membership is 388003 . - Team SGCCI',
            'route' => 17,
        ]);
        $this->line('   Result: '.($result2['success'] ? '✅ SUCCESS' : '❌ FAILED - '.$result2['error']));
        $this->newLine();

        // Test 3: Important notice (DCS=8, route=4)
        $this->info('📱 Test 3: Important Notice (DCS=8, route=4)...');
        $result3 = $this->sendSms([
            'user' => 'ADMSGCCI',
            'password' => 'Sgcci@25',
            'senderid' => 'CHAMBR',
            'channel' => 'Trans',
            'DCS' => 8,
            'flashsms' => 0,
            'number' => $phone,
            'text' => 'IMPORTANT NOTICE Dear Member (12322), SGCCI is transforming into Company. You are requested to kindly submit your Membership Application again as soon as possible. You can fill the form from sgcci.in For more details, Call 9023659280 – SGCCI',
            'route' => 4,
        ]);
        $this->line('   Result: '.($result3['success'] ? '✅ SUCCESS' : '❌ FAILED - '.$result3['error']));
        $this->newLine();

        // Summary
        $this->info('=== Summary ===');
        $this->line('Test 1 (Booking - DCS=0, route=17): '.($result1['success'] ? '✅ '.$result1['code'] : '❌ '.$result1['code']));
        $this->line('Test 2 (OTP - DCS=0, route=17): '.($result2['success'] ? '✅ '.$result2['code'] : '❌ '.$result2['code']));
        $this->line('Test 3 (Notice - DCS=8, route=4): '.($result3['success'] ? '✅ '.$result3['code'] : '❌ '.$result3['code']));

        $successCount = ($result1['success'] ? 1 : 0) + ($result2['success'] ? 1 : 0) + ($result3['success'] ? 1 : 0);

        if ($successCount > 0) {
            $this->newLine();
            $this->info("🎉 {$successCount} out of 3 templates sent successfully!");
            $this->info('Check your phone for messages.');
        } else {
            $this->newLine();
            $this->error('❌ All templates failed. This indicates DLT templates may not be registered or approved.');
        }

        return self::SUCCESS;
    }

    private function sendSms(array $params): array
    {
        try {
            $response = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', $params);
            $data = $response->json();

            return [
                'success' => isset($data['ErrorCode']) && $data['ErrorCode'] === '000',
                'code' => $data['ErrorCode'] ?? 'N/A',
                'error' => $data['ErrorMessage'] ?? 'Unknown error',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => 'EXC',
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
