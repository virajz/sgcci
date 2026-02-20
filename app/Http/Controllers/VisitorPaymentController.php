<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SendSmsMessage;
use App\Jobs\SendWhatsAppCampaign;
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

            // SMS disabled until DLT template is approved
            // Template ID 1707177157041193630 needs to be active in DLT portal
            // if ($isSuccess && config('services.sms.enabled')) {
            //     $exhibition = $visitor->exhibition;
            //     $passLink = route('visitor.scan', [
            //         'exhibition' => $exhibition->slug,
            //         'registrationCode' => $visitor->registration_code,
            //     ]);
            //
            //     SendSmsMessage::dispatch(
            //         template: 'visitor_registration_confirmed',
            //         phoneCode: '',
            //         phoneNumber: $visitor->phone_number,
            //         variables: [
            //             explode(' ', trim($visitor->name))[0],
            //             $passLink,
            //         ],
            //         templateId: '1707177157041193630'
            //     );
            // }

            if ($isSuccess && config('services.whatsapp.enabled')) {
                $this->sendWhatsAppNotification($visitor, $visitor->exhibition);
            }

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

    private function sendWhatsAppNotification(ExhibitionVisitor $visitor, $exhibition): void
    {
        // Format exhibition dates and payment amount
        $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');
        $amountPaid = '₹'.number_format((float) $visitor->payment_amount, 2);

        // Send WhatsApp for primary visitor
        $primaryFirstName = explode(' ', trim($visitor->name))[0];
        $primaryImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $primaryFirstName,              // {{1}} - Name in title
                $exhibition->title,             // {{2}} - Exhibition
                $visitor->registration_code,    // {{3}} - Registration Code
                $primaryFirstName,              // {{4}} - Name in body
                $visitor->company_name ?: 'N/A', // {{5}} - Company
                $visitor->city,                 // {{6}} - City
                $amountPaid,                    // {{7}} - Amount
                $visitor->payment_transaction_id ?: 'N/A', // {{8}} - Transaction ID
                $visitor->payment_completed_at->format('d-m-Y'), // {{9}} - Payment Date
                $exhibitionDates,               // {{10}} - Exhibition Dates
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $primaryImageUrl,
                'filename' => 'visitor_pass_'.$visitor->registration_code,
            ]
        );

        // Send WhatsApp for each additional person
        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personFirstName = explode(' ', trim($person['name']))[0];
                $personImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image?personIndex='.$index;

                SendWhatsAppCampaign::dispatch(
                    campaignName: 'Paidregistration1',
                    phoneCode: '',
                    phoneNumber: $visitor->phone_number,
                    templateParams: [
                        $personFirstName,               // {{1}} - Name in title
                        $exhibition->title,             // {{2}} - Exhibition
                        $visitor->registration_code,    // {{3}} - Registration Code
                        $personFirstName,               // {{4}} - Name in body
                        $visitor->company_name ?: 'N/A', // {{5}} - Company
                        $visitor->city,                 // {{6}} - City
                        $amountPaid,                    // {{7}} - Amount
                        $visitor->payment_transaction_id ?: 'N/A', // {{8}} - Transaction ID
                        $visitor->payment_completed_at->format('d-m-Y'), // {{9}} - Payment Date
                        $exhibitionDates,               // {{10}} - Exhibition Dates
                    ],
                    paramsFallbackValue: [
                        'FirstName' => 'Guest',
                    ],
                    media: [
                        'url' => $personImageUrl,
                        'filename' => 'visitor_pass_'.$visitor->registration_code.'_person_'.($index + 1),
                    ]
                );
            }
        }
    }
}
