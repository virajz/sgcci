<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\BookingStatus;
use App\Jobs\SendSmsMessage;
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
            $previousAmountPaid = (float) $booking->amount_paid;

            // Get the payment amount from the response
            $paymentAmount = (float) ($responseData['amount'] ?? 0);

            // Update booking with payment details
            $updateData = [
                'payment_transaction_id' => $responseData['tracking_id'] ?? null,
                'payment_tracking_id' => $responseData['tracking_id'] ?? null,
                'payment_bank_ref_no' => $responseData['bank_ref_no'] ?? null,
                'payment_method' => $responseData['payment_mode'] ?? 'CCAvenue',
                'payment_status' => $responseData['order_status'] ?? 'Unknown',
                'payment_response' => json_encode($responseData),
            ];

            // If payment was successful, record it
            if ($this->ccavenueService->isPaymentSuccessful($responseData)) {
                // Record the payment in history
                $paymentHistory = $booking->payment_history ?? [];
                $paymentHistory[] = [
                    'amount' => $paymentAmount,
                    'method' => 'CCAvenue',
                    'transaction_id' => $responseData['tracking_id'] ?? null,
                    'bank_ref_no' => $responseData['bank_ref_no'] ?? null,
                    'payment_mode' => $responseData['payment_mode'] ?? null,
                    'recorded_at' => now()->toDateTimeString(),
                    'status' => 'success',
                ];

                // Update amounts
                $newAmountPaid = $previousAmountPaid + $paymentAmount;
                $newRemainingAmount = max(0, (float) $booking->total_with_gst - $newAmountPaid);

                $updateData['amount_paid'] = $newAmountPaid;
                $updateData['remaining_amount'] = $newRemainingAmount;
                $updateData['payment_history'] = $paymentHistory;
                $updateData['payment_amount'] = $paymentAmount;

                // If payment is fully complete
                if ($newRemainingAmount <= 0) {
                    $updateData['payment_completed_at'] = now();
                    $updateData['status'] = BookingStatus::PaymentCompleted;
                    $updateData['payment_due_at'] = null;

                    // Cancel any older pending bookings for the same stalls to prevent double-booking
                    $this->cancelConflictingBookings($booking);
                } else {
                    // Partial payment - clear the 3-day auto-release deadline since customer has shown commitment
                    $updateData['status'] = BookingStatus::PaymentPending;
                    $updateData['payment_due_at'] = null;
                }
            }

            $booking->update($updateData);

            // Log the response
            Log::info('CCAvenue Payment Response', [
                'booking_code' => $booking->booking_code,
                'status' => $responseData['order_status'] ?? 'Unknown',
                'tracking_id' => $responseData['tracking_id'] ?? null,
                'amount' => $paymentAmount,
                'total_paid' => $booking->amount_paid,
                'remaining' => $booking->remaining_amount,
            ]);

            // Send notifications ONLY if this is a NEW payment completion (prevent duplicates on refresh)
            if (
                $this->ccavenueService->isPaymentSuccessful($responseData)
                && ! $wasAlreadyCompleted
                && $paymentAmount > 0
            ) {
                // Determine which template to use
                $isFullPayment = $booking->remaining_amount <= 0;
                $campaignName = $isFullPayment ? 'payment_success' : 'partial_payment_success';

                // Send WhatsApp notification
                if (config('services.whatsapp.enabled')) {
                    SendWhatsAppCampaign::dispatch(
                        campaignName: $campaignName,
                        phoneCode: $booking->phone_code,
                        phoneNumber: $booking->phone_number,
                        templateParams: [
                            $booking->contact_person,                             // {{1}} Contact Person Name
                            $booking->exhibition->title,                          // {{2}} Exhibition Title
                            $booking->booking_code,                               // {{3}} Booking Code
                            number_format($paymentAmount, 2),                     // {{4}} Amount Paid
                            now()->format('M d, Y'),                              // {{5}} Payment Date
                            implode(', ', $booking->selected_stalls),             // {{6}} Confirmed Stalls
                            number_format((float) $booking->remaining_amount, 2), // {{7}} Remaining Amount (for partial)
                        ]
                    );

                    // Send WhatsApp notification to staff members
                    \App\Jobs\SendStaffWhatsAppNotifications::dispatch(
                        booking: $booking,
                        campaignName: $campaignName
                    );
                }

                // Send SMS notification
                if (config('services.sms.enabled')) {
                    if ($isFullPayment) {
                        // Full payment success SMS
                        SendSmsMessage::dispatch(
                            template: 'payment_success',
                            phoneCode: $booking->phone_code,
                            phoneNumber: $booking->phone_number,
                            variables: [
                                'contact_name' => explode(' ', trim($booking->contact_person))[0],
                                'amount' => number_format($paymentAmount, 0, '', ''),
                                'exhibition' => $this->abbreviateTitle($booking->exhibition->title),
                                'booking_code' => $booking->booking_code,
                                'date' => now()->format('d/m'),
                            ]
                        );
                    } else {
                        // Partial payment SMS
                        SendSmsMessage::dispatch(
                            template: 'partial_payment_received',
                            phoneCode: $booking->phone_code,
                            phoneNumber: $booking->phone_number,
                            variables: [
                                'contact_name' => explode(' ', trim($booking->contact_person))[0],
                                'amount' => number_format($paymentAmount, 0, '', ''),
                                'booking_code' => $booking->booking_code,
                                'remaining' => number_format($booking->remaining_amount, 0, '', ''),
                                'due_date' => $booking->payment_due_at?->format('d/m') ?? now()->addDays(7)->format('d/m'),
                            ]
                        );
                    }
                }
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

    /**
     * Abbreviate exhibition title for SMS.
     */
    private function abbreviateTitle(string $title, int $maxLength = 20): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        return substr($title, 0, $maxLength - 3).'...';
    }
}
