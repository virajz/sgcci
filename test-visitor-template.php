#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$phone = $argv[1] ?? '7874949091';
$name = $argv[2] ?? 'Test User';
$passUrl = $argv[3] ?? 'https://sgcci.test/visitor/pass/abc123';

echo "\n=== Testing Visitor Registration SMS Template ===\n";
echo "Phone: {$phone}\n";
echo "Name: {$name}\n";
echo "Pass URL: {$passUrl}\n\n";

// The exact template text from DLT: "Dear {#var#} You are registered for Auto Expo 2025 (8-11 Jan 2025) at Rajpath Club, Ahmedabad. Access your pass: {#var#} Team SGCCI"
$templateText = "Dear {$name} You are registered for Auto Expo 2025 (8-11 Jan 2025) at Rajpath Club, Ahmedabad. Access your pass: {$passUrl} Team SGCCI";

echo "📱 Sending with template ID 1707177157041193630...\n";
echo "Text: {$templateText}\n\n";

// Try with DCS=0, route=17 (like booking template)
$response = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', [
    'user' => 'ADMSGCCI',
    'password' => 'Sgcci@25',
    'senderid' => 'CHAMBR',
    'channel' => 'Trans',
    'DCS' => 0,
    'flashsms' => 0,
    'number' => $phone,
    'text' => $templateText,
    'route' => 17,
    'DLT_TE_ID' => '1707177157041193630',
]);

$data = $response->json();

echo 'Result: ';
if ($data['ErrorCode'] === '000') {
    echo "✅ SUCCESS!\n";
    echo 'MessageID: '.($data['MessageID'] ?? 'N/A')."\n";
    echo "\n🎉 Visitor registration SMS is working!\n";
    echo "Check your phone for the message.\n";
} else {
    echo "❌ FAILED\n";
    echo "Error Code: {$data['ErrorCode']}\n";
    echo "Error Message: {$data['ErrorMessage']}\n";

    echo "\n--- Trying alternative formats ---\n\n";

    // Try with pipe-delimited parameters (DLT format)
    echo "Attempt 2: Using pipe-delimited parameters...\n";
    $pipeText = "Dear |{$name}| You are registered for Auto Expo 2025 (8-11 Jan 2025) at Rajpath Club, Ahmedabad. Access your pass: |{$passUrl}| Team SGCCI";
    $r2 = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', [
        'user' => 'ADMSGCCI',
        'password' => 'Sgcci@25',
        'senderid' => 'CHAMBR',
        'channel' => 'Trans',
        'DCS' => 0,
        'flashsms' => 0,
        'number' => $phone,
        'text' => $pipeText,
        'route' => 17,
        'DLT_TE_ID' => '1707177157041193630',
    ]);
    $d2 = $r2->json();
    echo '   Result: '.($d2['ErrorCode'] === '000' ? '✅ SUCCESS' : '❌ '.$d2['ErrorMessage'])."\n\n";

    // Try without template ID
    echo "Attempt 3: Without template ID parameter...\n";
    $r3 = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', [
        'user' => 'ADMSGCCI',
        'password' => 'Sgcci@25',
        'senderid' => 'CHAMBR',
        'channel' => 'Trans',
        'DCS' => 0,
        'flashsms' => 0,
        'number' => $phone,
        'text' => $templateText,
        'route' => 17,
    ]);
    $d3 = $r3->json();
    echo '   Result: '.($d3['ErrorCode'] === '000' ? '✅ SUCCESS' : '❌ '.$d3['ErrorMessage'])."\n\n";

    echo "If all attempts failed, the template may not be approved in DLT.\n";
    echo "Contact your SMS provider to verify template status.\n";
}

echo "\n";
