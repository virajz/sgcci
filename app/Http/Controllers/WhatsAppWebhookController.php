<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\WhatsAppInquiry;
use App\Models\WhatsAppWebhookLog;
use App\Services\WhatsAppDirectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppWebhookController extends Controller
{
    public function receive(Request $request): JsonResponse|Response
    {
        WhatsAppWebhookLog::create([
            'method' => $request->method(),
            'path' => $request->path(),
            'headers' => $request->headers->all(),
            'payload' => $request->all() ?: null,
            'ip' => $request->ip(),
        ]);

        $this->processMessage($request->all());

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process an incoming WhatsApp message payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function processMessage(array $payload): void
    {
        $message = data_get($payload, 'data.message');

        if (! $message) {
            return;
        }

        // Only process inbound text messages from users
        if (data_get($message, 'sender') !== 'USER' || data_get($message, 'message_type') !== 'TEXT') {
            return;
        }

        $text = trim((string) data_get($message, 'message_content.text', ''));
        $phoneNumber = (string) data_get($message, 'phone_number', '');
        $userName = (string) data_get($message, 'userName', '');

        // Match: "I want to know more about {Brand Name} - {XXXXXX}"
        if (! preg_match('/^I want to know more about .+ - ([A-Z0-9]{6})\s*$/i', $text, $matches)) {
            return;
        }

        $codeSuffix = strtoupper($matches[1]);

        $booking = Booking::query()
            ->where('booking_code', 'like', '%'.$codeSuffix)
            ->first();

        if (! $booking) {
            Log::channel('whatsapp')->warning('WhatsApp webhook: no booking found for code suffix', [
                'code_suffix' => $codeSuffix,
                'from' => $phoneNumber,
            ]);

            return;
        }

        $this->sendProfileMessage($booking, $phoneNumber);
        $this->recordLead($booking, $phoneNumber, $userName);
    }

    /**
     * Send the exhibitor's profile message to the inquiry sender.
     */
    private function sendProfileMessage(Booking $booking, string $phoneNumber): void
    {
        $whatsapp = WhatsAppDirectService::fromConfig();
        $type = $booking->profile_message_type ?? 'text';
        $text = $booking->profile_message_text ?? '';
        $mediaPath = $booking->profile_message_media;
        $mediaOriginalName = $booking->profile_message_media_original_name ?? 'file';

        $fallback = "Thank you for taking interest in {$booking->brand_name}, a representative will soon get in touch with you to know more about you.";

        match ($type) {
            'text' => $whatsapp->sendText($phoneNumber, $text ?: $fallback),
            'image' => $this->sendMedia(
                fn (string $url) => $whatsapp->sendImage($phoneNumber, $url, $text),
                $mediaPath,
                $type,
                $booking->brand_name,
            ),
            'video', 'audio', 'document' => $this->sendMedia(
                fn (string $url) => $whatsapp->sendDocument($phoneNumber, $url, $mediaOriginalName, $text),
                $mediaPath,
                $type,
                $booking->brand_name,
            ),
            default => $whatsapp->sendText($phoneNumber, $fallback),
        };
    }

    /**
     * Resolve the public URL for a stored media file and invoke the sender callback.
     *
     * @param  callable(string): bool  $sender
     */
    private function sendMedia(callable $sender, ?string $mediaPath, string $type, string $brandName): void
    {
        if (! $mediaPath) {
            Log::channel('whatsapp')->warning('WhatsApp webhook: no media path for booking', [
                'brand_name' => $brandName,
                'type' => $type,
            ]);

            return;
        }

        $url = Storage::url($mediaPath);

        // Prepend app URL if the URL is relative (local disk)
        if (! str_starts_with($url, 'http')) {
            $url = rtrim(config('app.url'), '/').'/'.ltrim($url, '/');
        }

        $sender($url);
    }

    /**
     * Record the WhatsApp sender as an inquiry for the exhibitor.
     */
    private function recordLead(Booking $booking, string $phoneNumber, string $userName): void
    {
        if (! $phoneNumber) {
            return;
        }

        WhatsAppInquiry::firstOrCreate(
            [
                'booking_id' => $booking->id,
                'phone_number' => $phoneNumber,
            ],
            [
                'name' => $userName ?: null,
                'received_at' => now(),
            ]
        );
    }
}
