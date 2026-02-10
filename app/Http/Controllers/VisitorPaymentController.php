<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExhibitionVisitor;
use App\Services\CCAvenueService;
use App\VisitorRegistrationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VisitorPaymentController extends Controller
{
    public function __construct(
        private readonly CCAvenueService $ccavenueService
    ) {}

    /**
     * Initiate payment for a visitor registration.
     */
    public function initiate(Request $request, string $registrationCode)
    {
        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->firstOrFail();

        if ($visitor->status === VisitorRegistrationStatus::Confirmed) {
            return redirect()->route('visitors-registration.thank-you', [
                'exhibition' => $visitor->exhibition,
                'registrationCode' => $registrationCode,
            ])->with('info', 'Registration is already confirmed.');
        }

        $visitor->update(['payment_initiated_at' => now()]);

        $encryptedData = $this->ccavenueService->generateEncryptedVisitorRequest($visitor);

        Log::info('Visitor Payment Initiated', [
            'registration_code' => $registrationCode,
            'amount' => $visitor->payment_amount,
        ]);

        return view('visitor-payment.redirect', [
            'encRequest' => $encryptedData,
            'accessCode' => $this->ccavenueService->getAccessCode(),
            'gatewayUrl' => $this->ccavenueService->getGatewayUrl(),
            'visitor' => $visitor,
        ]);
    }

    /**
     * Handle CCAvenue response for visitor payment.
     */
    public function response(Request $request)
    {
        $encResponse = $request->input('encResp');

        if (! $encResponse) {
            Log::error('Visitor CCAvenue Response Missing');

            return redirect()->route('home')->with('error', 'Invalid payment response.');
        }

        try {
            $responseData = $this->ccavenueService->parseResponse($encResponse);

            $visitor = ExhibitionVisitor::where('registration_code', $responseData['order_id'])
                ->firstOrFail();

            $isSuccess = $this->ccavenueService->isPaymentSuccessful($responseData);

            $visitor->update([
                'payment_transaction_id' => $responseData['tracking_id'] ?? null,
                'payment_tracking_id' => $responseData['tracking_id'] ?? null,
                'payment_bank_ref_no' => $responseData['bank_ref_no'] ?? null,
                'payment_method' => $responseData['payment_mode'] ?? 'CCAvenue',
                'payment_status' => $responseData['order_status'] ?? 'Unknown',
                'payment_response' => $responseData,
                'status' => $isSuccess
                    ? VisitorRegistrationStatus::Confirmed
                    : VisitorRegistrationStatus::PaymentFailed,
                'payment_completed_at' => $isSuccess ? now() : null,
            ]);

            Log::info('Visitor Payment Response', [
                'registration_code' => $visitor->registration_code,
                'status' => $responseData['order_status'] ?? 'Unknown',
                'tracking_id' => $responseData['tracking_id'] ?? null,
            ]);

            return view('visitor-payment.response', [
                'visitor' => $visitor,
                'responseData' => $responseData,
                'isSuccess' => $isSuccess,
            ]);
        } catch (\Exception $e) {
            Log::error('Visitor Payment Response Processing Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('home')->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle visitor payment cancellation.
     */
    public function cancel(Request $request)
    {
        $encResponse = $request->input('encResp');

        if ($encResponse) {
            try {
                $responseData = $this->ccavenueService->parseResponse($encResponse);
                $visitor = ExhibitionVisitor::where('registration_code', $responseData['order_id'])->first();

                if ($visitor) {
                    Log::info('Visitor Payment Cancelled', [
                        'registration_code' => $visitor->registration_code,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Visitor Payment Cancellation Processing Failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('home')->with('info', 'Payment was cancelled.');
    }
}
