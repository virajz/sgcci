<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\ExhibitionVisitor;
use Illuminate\Support\Facades\Http;
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
        // Calculate payment amount based on payment history
        $paymentAmount = $this->calculatePaymentAmount($booking);

        $merchantData = [
            'merchant_id' => $this->merchantId,
            'order_id' => $booking->booking_code,
            'amount' => number_format($paymentAmount, 2, '.', ''),
            'currency' => config('services.ccavenue.currency', 'INR'),
            'redirect_url' => config('services.ccavenue.redirect_url'),
            'cancel_url' => config('services.ccavenue.cancel_url'),
            'language' => 'EN',
            'billing_name' => $booking->contact_person,
            'billing_address' => $booking->city, // Using city as address (mandatory field)
            'billing_city' => $booking->city,
            'billing_state' => 'Gujarat', // Default to Gujarat for SGCCI
            'billing_zip' => '380009', // Default to SGCCI headquarters pincode
            'billing_country' => 'India',
            'billing_tel' => str_replace(' ', '', $booking->phone_code . $booking->phone_number),
            'billing_email' => $booking->email,
            'merchant_param1' => (string) $booking->id,
            'merchant_param2' => (string) $booking->exhibition_id,
            'merchant_param3' => (string) $booking->brand_name,
        ];

        return $merchantData;
    }

    /**
     * Calculate the payment amount based on remaining balance.
     * Always charges the full remaining amount.
     */
    public function calculatePaymentAmount(Booking $booking): float
    {
        return (float) $booking->remaining_amount;
    }

    /**
     * Generate encrypted request for CCAvenue
     */
    public function generateEncryptedRequest(Booking $booking): string
    {
        $merchantData = $this->preparePaymentData($booking);

        $dataString = '';
        foreach ($merchantData as $key => $value) {
            $dataString .= $key . '=' . $value . '&';
        }

        return $this->encrypt(rtrim($dataString, '&'));
    }

    /**
     * Prepare payment data for visitor registration.
     */
    public function prepareVisitorPaymentData(ExhibitionVisitor $visitor): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'order_id' => $visitor->registration_code,
            'amount' => number_format((float) $visitor->payment_amount, 2, '.', ''),
            'currency' => config('services.ccavenue.currency', 'INR'),
            'redirect_url' => config('services.ccavenue.visitor_redirect_url'),
            'cancel_url' => config('services.ccavenue.visitor_cancel_url'),
            'language' => 'EN',
            'billing_name' => $visitor->name,
            'billing_address' => $visitor->city,
            'billing_city' => $visitor->city,
            'billing_state' => $visitor->state,
            'billing_zip' => '000000',
            'billing_country' => 'India',
            'billing_tel' => $visitor->phone_number,
            'billing_email' => $visitor->email ?? 'noreply@sgcci.in',
            'merchant_param1' => (string) $visitor->id,
            'merchant_param2' => (string) $visitor->exhibition_id,
            'merchant_param3' => 'visitor_registration',
            'merchant_param4' => $visitor->name,
            'merchant_param5' => $visitor->phone_number,
        ];
    }

    /**
     * Generate encrypted request for visitor payment.
     */
    public function generateEncryptedVisitorRequest(ExhibitionVisitor $visitor): string
    {
        $merchantData = $this->prepareVisitorPaymentData($visitor);

        $dataString = '';
        foreach ($merchantData as $key => $value) {
            $dataString .= $key . '=' . $value . '&';
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
     * Query CCAvenue's Order Status API for a given order ID.
     *
     * Returns an array with at minimum:
     *   - 'order_status' : "Success" | "Failure" | "Aborted" | "Not Found" | "Invalid" | "Timeout"
     *   - 'tracking_id'  : CCAvenue reference number (if available)
     *   - 'amount'       : amount charged (if available)
     *   - raw fields from CCAvenue
     *
     * @param  string  $orderId  The order_id / registration_code used when initiating payment
     * @return array<string, mixed>
     */
    public function checkOrderStatus(string $orderId): array
    {
        $requestPayload = json_encode([
            'merchant_id' => $this->merchantId,
            'order_no' => $orderId,
        ]);
        $encRequest = $this->encrypt($requestPayload);

        $apiUrl = $this->testMode
            ? 'https://test.ccavenue.com/apis/servlet/DoWebTrans'
            : 'https://login.ccavenue.com/apis/servlet/DoWebTrans';

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($apiUrl, [
                    'enc_request' => $encRequest,
                    'access_code' => $this->accessCode,
                    'request_type' => 'JSON',
                    'response_type' => 'JSON',
                    'command' => 'orderStatusTracker',
                    'version' => '1.1',
                ]);

            if (! $response->successful()) {
                Log::error('CCAvenue Order Status API HTTP error', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                ]);

                return ['order_status' => 'Timeout'];
            }

            // CCAvenue returns URL-encoded form data: "status=0&enc_response=..."
            parse_str(trim($response->body()), $parsed);

            if (empty($parsed['enc_response'])) {
                Log::warning('CCAvenue Order Status API returned no enc_response', [
                    'order_id' => $orderId,
                    'body' => $response->body(),
                ]);

                return ['order_status' => 'Invalid'];
            }

            $decrypted = $this->decrypt($parsed['enc_response']);
            $data = json_decode($decrypted, true) ?? [];

            // The status API returns "Shipped" for a successful payment; normalise
            // to "Success" so it is consistent with the redirect response handler.
            $rawStatus = $data['order_status'] ?? 'Unknown';
            $normalisedStatus = match (strtolower($rawStatus)) {
                'shipped' => 'Success',
                'awaited', 'initiated' => 'Awaited',
                default => $rawStatus,
            };

            $normalised = [
                'order_status' => $normalisedStatus,
                'tracking_id' => $data['reference_no'] ?? null,
                'bank_ref_no' => $data['order_bank_ref_no'] ?? null,
                'amount' => $data['order_amt'] ?? null,
                'order_id' => $data['order_no'] ?? $orderId,
                'payment_mode' => $data['order_card_name'] ?? null,
            ];

            Log::info('CCAvenue Order Status API response', [
                'order_id' => $orderId,
                'raw_status' => $rawStatus,
                'order_status' => $normalisedStatus,
                'tracking_id' => $normalised['tracking_id'],
            ]);

            return $normalised;
        } catch (\Exception $e) {
            Log::error('CCAvenue Order Status API exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return ['order_status' => 'Timeout'];
        }
    }

    private function pkcs5Pad(string $plainText, int $blockSize): string
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);

        return $plainText . str_repeat(chr($pad), $pad);
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
