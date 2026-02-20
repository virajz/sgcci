#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$phone = $argv[1] ?? '7874949091';

echo "\n=== Testing 3 SMS Templates on {$phone} ===\n\n";

// Test 1: Booking template (DCS=0, route=17)
echo "📱 Test 1: Booking Confirmation (DCS=0, route=17)...\n";
$r1 = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', [
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
$d1 = $r1->json();
echo '   Result: '.($d1['ErrorCode'] === '000' ? '✅ SUCCESS' : '❌ FAILED - '.$d1['ErrorMessage'])."\n\n";

// Test 2: OTP template (DCS=0, route=17)
echo "📱 Test 2: OTP Verification (DCS=0, route=17)...\n";
$r2 = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', [
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
$d2 = $r2->json();
echo '   Result: '.($d2['ErrorCode'] === '000' ? '✅ SUCCESS' : '❌ FAILED - '.$d2['ErrorMessage'])."\n\n";

// Test 3: Important notice (DCS=8, route=4)
echo "📱 Test 3: Important Notice (DCS=8, route=4)...\n";
$r3 = Http::get('http://smsl.myappstores.com/api/mt/SendSMS', [
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
$d3 = $r3->json();
echo '   Result: '.($d3['ErrorCode'] === '000' ? '✅ SUCCESS' : '❌ FAILED - '.$d3['ErrorMessage'])."\n\n";

// Summary
echo "=== Summary ===\n";
echo 'Test 1 (Booking - DCS=0, route=17): '.($d1['ErrorCode'] === '000' ? '✅' : '❌').' Code: '.$d1['ErrorCode']."\n";
echo 'Test 2 (OTP - DCS=0, route=17): '.($d2['ErrorCode'] === '000' ? '✅' : '❌').' Code: '.$d2['ErrorCode']."\n";
echo 'Test 3 (Notice - DCS=8, route=4): '.($d3['ErrorCode'] === '000' ? '✅' : '❌').' Code: '.$d3['ErrorCode']."\n";

$successCount = (($d1['ErrorCode'] === '000') ? 1 : 0) + (($d2['ErrorCode'] === '000') ? 1 : 0) + (($d3['ErrorCode'] === '000') ? 1 : 0);

if ($successCount > 0) {
    echo "\n🎉 {$successCount} out of 3 templates sent successfully!\n";
    echo "Check your phone for messages.\n";
} else {
    echo "\n❌ All templates failed.\n";
}

echo "\n";
