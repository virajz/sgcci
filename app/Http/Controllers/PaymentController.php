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

            // Track if this is a new payment completion (to prevent duplicate WhatsApp on refresh)
            $wasAlreadyCompleted = $booking->status === BookingStatus::PaymentCompleted;

            // Update booking with payment details
            $updateData = [
                'payment_transaction_id' => $responseData['tracking_id'] ?? null,
                'payment_tracking_id' => $responseData['tracking_id'] ?? null,
                'payment_bank_ref_no' => $responseData['bank_ref_no'] ?? null,
                'payment_method' => $responseData['payment_mode'] ?? 'CCAvenue',
                'payment_status' => $responseData['order_status'] ?? 'Unknown',
                'payment_amount' => $responseData['amount'] ?? $booking->total_with_gst,
                'payment_response' => json_encode($responseData),
                'payment_completed_at' => $this->ccavenueService->isPaymentSuccessful($responseData) ? now() : null,
            ];

            // Update booking status to PaymentCompleted if payment was successful
            if ($this->ccavenueService->isPaymentSuccessful($responseData)) {
                $updateData['status'] = BookingStatus::PaymentCompleted;

                // Cancel any older pending bookings for the same stalls to prevent double-booking
                $this->cancelConflictingBookings($booking);
            }

            $booking->update($updateData);

            // Log the response
            Log::info('CCAvenue Payment Response', [
                'booking_code' => $booking->booking_code,
                'status' => $responseData['order_status'] ?? 'Unknown',
                'tracking_id' => $responseData['tracking_id'] ?? null,
            ]);

            // Send WhatsApp notification ONLY if this is a NEW payment completion (prevent duplicates on refresh)
            if (
                $this->ccavenueService->isPaymentSuccessful($responseData)
                && config('services.whatsapp.enabled')
                && ! $wasAlreadyCompleted
            ) {
                SendWhatsAppCampaign::dispatch(
                    campaignName: 'payment_success',
                    phoneCode: $booking->phone_code,
                    phoneNumber: $booking->phone_number,
                    templateParams: [
                        $booking->contact_person,                             // {{1}} Contact Person Name
                        $booking->exhibition->title,                          // {{2}} Exhibition Title
                        $booking->booking_code,                               // {{3}} Booking Code
                        number_format((float) ($responseData['amount'] ?? $booking->total_with_gst), 2), // {{4}} Amount Paid
                        now()->format('M d, Y'),                              // {{5}} Payment Date
                        implode(', ', $booking->selected_stalls),             // {{6}} Confirmed Stalls
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

    /**
     * Cancel older pending bookings that conflict with the newly paid booking
     */
    private function cancelConflictingBookings(Booking $paidBooking): void
    {
        // Find all other bookings for the same exhibition with overlapping stalls
        Booking::where('exhibition_id', $paidBooking->exhibition_id)
            ->where('id', '!=', $paidBooking->id)
            ->whereIn('status', [
                BookingStatus::PendingApproval,
                BookingStatus::ApprovedByAdmin,
                BookingStatus::Allotted,
                BookingStatus::PaymentPending,
            ])
            ->get()
            ->each(function ($booking) use ($paidBooking) {
                // Check if this booking has any stalls in common with the paid booking
                $overlappingStalls = array_intersect(
                    $booking->selected_stalls ?? [],
                    $paidBooking->selected_stalls ?? []
                );

                if (! empty($overlappingStalls)) {
                    $booking->update([
                        'status' => BookingStatus::Cancelled,
                        'rejection_reason' => 'Auto-cancelled: Stalls '.implode(', ', $overlappingStalls).
                            ' were booked by another customer (Booking #'.$paidBooking->booking_code.').',
                    ]);

                    Log::info('Auto-cancelled conflicting booking', [
                        'cancelled_booking' => $booking->booking_code,
                        'winning_booking' => $paidBooking->booking_code,
                        'overlapping_stalls' => $overlappingStalls,
                    ]);
                }
            });
    }
}
