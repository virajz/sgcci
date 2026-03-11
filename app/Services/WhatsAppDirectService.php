<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppDirectService
{
    public function __construct(
        private string $projectId,
        private string $apiKey,
        private string $testNumber,
    ) {}

    /**
     * Send a plain text WhatsApp message.
     */
    public function sendText(string $to, string $body): bool
    {
        return $this->send($to, [
            'type' => 'text',
            'recipient_type' => 'individual',
            'text' => ['body' => $body],
        ]);
    }

    /**
     * Send an image WhatsApp message.
     */
    public function sendImage(string $to, string $imageUrl, string $caption = ''): bool
    {
        $image = ['link' => $imageUrl];

        if ($caption !== '') {
            $image['caption'] = $caption;
        }

        return $this->send($to, [
            'type' => 'image',
            'image' => $image,
        ]);
    }

    /**
     * Send a document WhatsApp message.
     */
    public function sendDocument(string $to, string $documentUrl, string $filename, string $caption = ''): bool
    {
        $document = [
            'link' => $documentUrl,
            'filename' => $filename,
        ];

        if ($caption !== '') {
            $document['caption'] = $caption;
        }

        return $this->send($to, [
            'type' => 'document',
            'document' => $document,
        ]);
    }

    /**
     * Build the API URL for this project.
     */
    private function apiUrl(): string
    {
        return "https://connect.api-wa.co/project-apis/v1/project/{$this->projectId}/messages";
    }

    /**
     * Send a message payload to the WhatsApp API.
     */
    private function send(string $to, array $payload): bool
    {
        $to = ltrim(preg_replace('/\s+/', '', $to), '+');

        $body = array_merge(['to' => $to], $payload);

        Log::channel('whatsapp')->info('Sending WhatsApp direct message', [
            'to' => $to,
            'type' => $payload['type'],
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-WA-Project-API-Pwd' => $this->apiKey,
            ])->post($this->apiUrl(), $body);

            if ($response->successful()) {
                Log::channel('whatsapp')->info('WhatsApp direct message sent', [
                    'to' => $to,
                    'type' => $payload['type'],
                    'response' => $response->json(),
                ]);

                return true;
            }

            Log::channel('whatsapp')->error('WhatsApp direct message failed', [
                'to' => $to,
                'type' => $payload['type'],
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('WhatsApp direct message exception', [
                'to' => $to,
                'type' => $payload['type'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a new instance from config.
     */
    public static function fromConfig(): self
    {
        return new self(
            projectId: config('services.whatsapp_direct.project_id'),
            apiKey: config('services.whatsapp_direct.api_key'),
            testNumber: config('services.whatsapp_direct.test_number'),
        );
    }

    /**
     * Get the configured test number.
     */
    public function testNumber(): string
    {
        return $this->testNumber;
    }
}
