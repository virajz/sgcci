<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebugSmsApi extends Command
{
    protected $signature = 'sms:debug {phone}';

    protected $description = 'Debug SMS API with different parameter formats';

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $apiUrl = config('services.sms.api_url');
        $username = config('services.sms.username');
        $password = config('services.sms.password');
        $senderId = config('services.sms.sender_id');
        $channel = config('services.sms.channel');
        $dcs = config('services.sms.dcs');
        $route = config('services.sms.route');

        $this->info('Testing different DLT template parameter formats...');
        $this->newLine();

        // Test 1: With DLT_TE_ID parameter
        $this->test('Test 1: DLT_TE_ID parameter', function () use ($apiUrl, $username, $password, $senderId, $channel, $dcs, $route, $phone) {
            $response = Http::get($apiUrl, [
                'user' => $username,
                'password' => $password,
                'senderid' => $senderId,
                'channel' => $channel,
                'DCS' => $dcs,
                'flashsms' => 0,
                'number' => $phone,
                'text' => 'Viraj|https://sgcci.test/test',
                'route' => $route,
                'DLT_TE_ID' => '1707177157041193630',
            ]);

            return $response;
        });

        // Test 2: With template_id parameter
        $this->test('Test 2: template_id parameter', function () use ($apiUrl, $username, $password, $senderId, $channel, $dcs, $route, $phone) {
            $response = Http::get($apiUrl, [
                'user' => $username,
                'password' => $password,
                'senderid' => $senderId,
                'channel' => $channel,
                'DCS' => $dcs,
                'flashsms' => 0,
                'number' => $phone,
                'text' => 'Viraj|https://sgcci.test/test',
                'route' => $route,
                'template_id' => '1707177157041193630',
            ]);

            return $response;
        });

        // Test 3: With PEID parameter (Principal Entity ID)
        $this->test('Test 3: PEID + DLT_TE_ID', function () use ($apiUrl, $username, $password, $senderId, $channel, $dcs, $route, $phone) {
            $response = Http::get($apiUrl, [
                'user' => $username,
                'password' => $password,
                'senderid' => $senderId,
                'channel' => $channel,
                'DCS' => $dcs,
                'flashsms' => 0,
                'number' => $phone,
                'text' => 'Viraj|https://sgcci.test/test',
                'route' => $route,
                'PEID' => '', // Add if you have Principal Entity ID
                'DLT_TE_ID' => '1707177157041193630',
            ]);

            return $response;
        });

        // Test 4: Variables in curly braces format
        $this->test('Test 4: {var1}{var2} format', function () use ($apiUrl, $username, $password, $senderId, $channel, $dcs, $route, $phone) {
            $response = Http::get($apiUrl, [
                'user' => $username,
                'password' => $password,
                'senderid' => $senderId,
                'channel' => $channel,
                'DCS' => $dcs,
                'flashsms' => 0,
                'number' => $phone,
                'text' => '{Viraj}{https://sgcci.test/test}',
                'route' => $route,
                'DLT_TE_ID' => '1707177157041193630',
            ]);

            return $response;
        });

        $this->newLine();
        $this->info('Check storage/logs/laravel.log for full API responses');

        return self::SUCCESS;
    }

    private function test(string $label, callable $callback): void
    {
        $this->info($label);

        try {
            $response = $callback();
            $data = $response->json();

            if (isset($data['ErrorCode']) && $data['ErrorCode'] === '000') {
                $this->line('  ✓ <fg=green>SUCCESS</> - '.($data['ErrorMessage'] ?? 'OK'));
            } else {
                $this->line('  ✗ <fg=red>FAILED</> - '.($data['ErrorMessage'] ?? $data['ErrorCode'] ?? 'Unknown error'));
            }

            Log::info($label, ['response' => $data]);
        } catch (\Exception $e) {
            $this->line('  ✗ <fg=red>EXCEPTION</> - '.$e->getMessage());
            Log::error($label, ['error' => $e->getMessage()]);
        }

        $this->newLine();
    }
}
