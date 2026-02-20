<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        private string $apiUrl,
        private string $username,
        private string $password,
        private string $senderId,
        private string $channel,
        private int $dcs,
        private int $route,
    ) {}

    /**
     * Send an SMS message.
     */
    public function send(
        string $phoneNumber,
        string $message,
        bool $flashSms = false
    ): bool {
        try {
            $response = Http::get($this->apiUrl, [
                'user' => $this->username,
                'password' => $this->password,
                'senderid' => $this->senderId,
                'channel' => $this->channel,
                'DCS' => $this->dcs,
                'flashsms' => $flashSms ? 1 : 0,
                'number' => $this->sanitizePhoneNumber($phoneNumber),
                'text' => $message,
                'route' => $this->route,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::channel('sms')->info('SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'message' => $message,
                    'message_length' => strlen($message),
                    'api_response' => $responseData,
                    'job_id' => $responseData['JobId'] ?? null,
                    'message_id' => $responseData['MessageData'][0]['MessageId'] ?? null,
                ]);

                return true;
            }

            Log::channel('sms')->error('SMS sending failed', [
                'phone' => $phoneNumber,
                'message' => $message,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('sms')->error('SMS sending exception', [
                'phone' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a template-based SMS message.
     */
    public function sendTemplate(string $template, string $phoneNumber, array $variables): bool
    {
        $message = $this->getTemplate($template, $variables);

        if (strlen($message) > 160) {
            Log::channel('sms')->warning('SMS message exceeds 160 characters', [
                'template' => $template,
                'length' => strlen($message),
                'phone' => $phoneNumber,
                'message' => $message,
                'variables' => $variables,
            ]);
        }

        // Log template usage before sending
        Log::channel('sms')->info('Sending SMS from template', [
            'template' => $template,
            'phone' => $phoneNumber,
            'variables' => $variables,
        ]);

        return $this->send($phoneNumber, $message);
    }

    /**
     * Get SMS template with variables replaced.
     */
    private function getTemplate(string $template, array $variables): string
    {
        return match ($template) {
            'booking_confirmation_payment' => sprintf(
                'Dear %s Your booking for %s is CONFIRMED. Booking No: %s Pay by %s: %s Team SGCCI',
                $variables['contact_name'],
                $variables['exhibition'],
                $variables['booking_code'],
                $variables['due_date'],
                $variables['payment_link']
            ),

            'booking_received' => sprintf(
                'Dear %s Your booking request for %s received. Booking No: %s Under review. You\'ll hear from us soon. Team SGCCI',
                $variables['contact_name'],
                $variables['exhibition'],
                $variables['booking_code']
            ),

            'booking_rejected' => sprintf(
                'Dear %s Your booking %s for %s could not be approved. Reason: %s Contact us for details. Team SGCCI',
                $variables['contact_name'],
                $variables['booking_code'],
                $variables['exhibition'],
                $variables['reason']
            ),

            'payment_success' => sprintf(
                'Payment received! Dear %s Rs.%s paid for %s Booking: %s on %s. Thank you! Team SGCCI',
                $variables['contact_name'],
                $variables['amount'],
                $variables['exhibition'],
                $variables['booking_code'],
                $variables['date']
            ),

            'partial_payment_received' => sprintf(
                'Dear %s Partial payment Rs.%s received for %s. Remaining: Rs.%s Due: %s Team SGCCI',
                $variables['contact_name'],
                $variables['amount'],
                $variables['booking_code'],
                $variables['remaining'],
                $variables['due_date']
            ),

            'payment_reminder' => sprintf(
                'REMINDER: Dear %s Payment pending for %s. Amount: Rs.%s Due: %s (%s days left) Team SGCCI',
                $variables['contact_name'],
                $variables['booking_code'],
                $variables['amount'],
                $variables['due_date'],
                $variables['days']
            ),

            'staff_booking_received' => sprintf(
                'NEW BOOKING: %s - %s Code: %s Stalls: %s Area: %ssqm Amt: Rs.%s - SGCCI Admin',
                $variables['contact_name'],
                $variables['exhibition'],
                $variables['booking_code'],
                $variables['stalls'],
                $variables['area'],
                $variables['amount']
            ),

            'staff_booking_approved' => sprintf(
                'APPROVED: %s - %s Stalls: %s Amt: Rs.%s Due: %s Payment link sent - SGCCI Admin',
                $variables['contact_name'],
                $variables['booking_code'],
                $variables['stalls'],
                $variables['amount'],
                $variables['due_date']
            ),

            'staff_payment_received' => sprintf(
                'PAYMENT RCVD: %s - %s Amt: Rs.%s Date: %s Exhibition: %s - SGCCI Admin',
                $variables['contact_name'],
                $variables['booking_code'],
                $variables['amount'],
                $variables['date'],
                $variables['exhibition']
            ),

            'visitor_registration_confirmed' => sprintf(
                'Dear %s You are registered for %s. Access your pass: %s Team SGCCI',
                $variables['name'],
                $variables['exhibition'],
                $variables['pass_link']
            ),

            default => throw new \InvalidArgumentException("Unknown SMS template: {$template}")
        };
    }

    /**
     * Sanitize phone number to 10 digits.
     */
    private function sanitizePhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If starts with +91 or 91, remove it
        if (str_starts_with($cleaned, '91') && strlen($cleaned) > 10) {
            $cleaned = substr($cleaned, 2);
        }

        // Return last 10 digits
        return substr($cleaned, -10);
    }

    /**
     * Create a new SMS service instance from config.
     */
    public static function fromConfig(): self
    {
        return new self(
            apiUrl: config('services.sms.api_url'),
            username: config('services.sms.username'),
            password: config('services.sms.password'),
            senderId: config('services.sms.sender_id'),
            channel: config('services.sms.channel'),
            dcs: config('services.sms.dcs'),
            route: config('services.sms.route'),
        );
    }
}
