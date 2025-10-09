<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class CCAvenueService
{
    private string $workingKey;

    private string $accessCode;

    private string $merchantId;

    private bool $testMode;

    public function __construct()
    {
        $this->workingKey = config('services.ccavenue.working_key');
        $this->accessCode = config('services.ccavenue.access_code');
        $this->merchantId = config('services.ccavenue.merchant_id');
        $this->testMode = config('services.ccavenue.test_mode', true);
    }

    /**
     * Encrypt data for CCAvenue request
     */
    public function encrypt(string $plainText): string
    {
        $secretKey = $this->hextobin(md5($this->workingKey));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E, 0x0F);

        $plainPad = $this->pkcs5Pad($plainText, 16);
        $encryptedText = openssl_encrypt($plainPad, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $initVector);

        return bin2hex($encryptedText);
    }

    /**
     * Decrypt data from CCAvenue response
     */
    public function decrypt(string $encryptedText): string
    {
        $secretKey = $this->hextobin(md5($this->workingKey));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E, 0x0F);

        $encryptedText = $this->hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $initVector);

        return rtrim($decryptedText, "\0");
    }

    /**
     * Prepare payment data for booking
     */
    public function preparePaymentData(Booking $booking): array
    {
        $merchantData = [
            'merchant_id' => $this->merchantId,
            'order_id' => $booking->booking_code,
            'amount' => number_format((float) $booking->total_with_gst, 2, '.', ''),
            'currency' => config('services.ccavenue.currency', 'INR'),
            'redirect_url' => config('services.ccavenue.redirect_url'),
            'cancel_url' => config('services.ccavenue.cancel_url'),
            'language' => 'EN',
            'billing_name' => $booking->contact_person,
            'billing_tel' => $booking->phone_code.$booking->phone_number,
            'billing_email' => $booking->email,
            'billing_city' => $booking->city,
            'billing_country' => 'India',
            'merchant_param1' => $booking->id,
            'merchant_param2' => $booking->exhibition_id,
            'merchant_param3' => $booking->brand_name,
        ];

        return $merchantData;
    }

    /**
     * Generate encrypted request for CCAvenue
     */
    public function generateEncryptedRequest(Booking $booking): string
    {
        $merchantData = $this->preparePaymentData($booking);

        $dataString = '';
        foreach ($merchantData as $key => $value) {
            $dataString .= $key.'='.$value.'&';
        }

        return $this->encrypt(rtrim($dataString, '&'));
    }

    /**
     * Parse CCAvenue response
     */
    public function parseResponse(string $encResponse): array
    {
        try {
            $decryptedString = $this->decrypt($encResponse);
            $responseData = [];

            parse_str($decryptedString, $responseData);

            Log::info('CCAvenue Response Parsed', [
                'order_id' => $responseData['order_id'] ?? null,
                'order_status' => $responseData['order_status'] ?? null,
                'tracking_id' => $responseData['tracking_id'] ?? null,
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('CCAvenue Response Parsing Failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if payment was successful
     */
    public function isPaymentSuccessful(array $responseData): bool
    {
        return isset($responseData['order_status']) &&
            strtolower($responseData['order_status']) === 'success';
    }

    /**
     * Get CCAvenue gateway URL
     */
    public function getGatewayUrl(): string
    {
        return $this->testMode
            ? 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction'
            : 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    }

    /**
     * Get access code
     */
    public function getAccessCode(): string
    {
        return $this->accessCode;
    }

    /**
     * PKCS5 padding
     */
    private function pkcs5Pad(string $plainText, int $blockSize): string
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);

        return $plainText.str_repeat(chr($pad), $pad);
    }

    /**
     * Convert hexadecimal to binary
     */
    private function hextobin(string $hexString): string
    {
        $length = strlen($hexString);
        $binString = '';
        $count = 0;

        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack('H*', $subString);

            if ($count === 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }

        return $binString;
    }
}
