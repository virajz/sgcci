<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\BookingStatus;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use App\Services\CCAvenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CCAvenueService $ccavenueService
    ) {}

    /**
     * Initiate payment for a booking
     */
    public function initiate(Request $request, string $bookingCode)
    {
        $booking = Booking::where('booking_code', $bookingCode)->firstOrFail();

        // Verify booking is eligible for payment
        if (! in_array($booking->status, [BookingStatus::Allotted, BookingStatus::PaymentPending])) {
            return redirect()->route('exhibitions.booking.thank-you', [
                'exhibition' => $booking->exhibition_id,
                'bookingCode' => $bookingCode,
            ])->with('error', 'This booking is not approved for payment.');
        }

        if ($booking->payment_completed_at) {
            return redirect()->route('exhibitions.booking.thank-you', [
                'exhibition' => $booking->exhibition_id,
                'bookingCode' => $bookingCode,
            ])->with('info', 'Payment has already been completed for this booking.');
        }

        // Update payment initiated timestamp
        $booking->update([
            'payment_initiated_at' => now(),
        ]);

        // Generate encrypted request
        $encryptedData = $this->ccavenueService->generateEncryptedRequest($booking);

        Log::info('Payment Initiated', [
            'booking_code' => $bookingCode,
            'amount' => $booking->total_with_gst,
        ]);

        return view('payment.redirect', [
            'encRequest' => $encryptedData,
            'accessCode' => $this->ccavenueService->getAccessCode(),
            'gatewayUrl' => $this->ccavenueService->getGatewayUrl(),
            'booking' => $booking,
        ]);
    }

    /**
     * Handle CCAvenue response (callback)
     */
    public function response(Request $request)
    {
        $encResponse = $request->input('encResp');

        if (! $encResponse) {
            Log::error('CCAvenue Response Missing');

            return redirect()->route('home')->with('error', 'Invalid payment response.');
        }

        try {
            // Decrypt and parse response
            $responseData = $this->ccavenueService->parseResponse($encResponse);

            // Find booking
            $booking = Booking::where('booking_code', $responseData['order_id'])->firstOrFail();

            // Update booking with payment details
            $booking->update([
                'payment_transaction_id' => $responseData['tracking_id'] ?? null,
                'payment_tracking_id' => $responseData['tracking_id'] ?? null,
                'payment_bank_ref_no' => $responseData['bank_ref_no'] ?? null,
                'payment_method' => $responseData['payment_mode'] ?? 'CCAvenue',
                'payment_status' => $responseData['order_status'] ?? 'Unknown',
                'payment_amount' => $responseData['amount'] ?? $booking->total_with_gst,
                'payment_response' => json_encode($responseData),
                'payment_completed_at' => $this->ccavenueService->isPaymentSuccessful($responseData) ? now() : null,
            ]);

            // Log the response
            Log::info('CCAvenue Payment Response', [
                'booking_code' => $booking->booking_code,
                'status' => $responseData['order_status'] ?? 'Unknown',
                'tracking_id' => $responseData['tracking_id'] ?? null,
            ]);

            // Send WhatsApp notification for successful payment
            if ($this->ccavenueService->isPaymentSuccessful($responseData)) {
                SendWhatsAppCampaign::dispatch(
                    'payment_success',
                    $booking->phone_code . $booking->phone_number,
                    [
                        $booking->contact_person,
                        $booking->exhibition->name,
                        $booking->booking_code,
                        '₹ ' . number_format((float) $booking->total_with_gst, 2),
                        now()->format('M d, Y'),
                    ]
                );
            }

            return view('payment.response', [
                'booking' => $booking,
                'responseData' => $responseData,
                'isSuccess' => $this->ccavenueService->isPaymentSuccessful($responseData),
            ]);
        } catch (\Exception $e) {
            Log::error('Payment Response Processing Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('home')->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle payment cancellation
     */
    public function cancel(Request $request)
    {
        $encResponse = $request->input('encResp');

        if ($encResponse) {
            try {
                $responseData = $this->ccavenueService->parseResponse($encResponse);
                $booking = Booking::where('booking_code', $responseData['order_id'])->first();

                if ($booking) {
                    Log::info('Payment Cancelled', [
                        'booking_code' => $booking->booking_code,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Payment Cancellation Processing Failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('home')->with('info', 'Payment was cancelled.');
    }
}
