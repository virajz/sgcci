<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitions;

use App\Jobs\SendSmsMessage;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.front')]
class VisitorsRegistration extends Component
{
    #[Locked]
    public int $exhibitionId;

    #[Locked]
    public ?string $source = null;

    public int $currentStep = 1;

    public string $phoneNumber = '';

    public string $name = '';

    public string $companyName = '';

    public string $designation = '';

    public string $state = '';

    public string $city = '';

    public string $email = '';

    public string $businessSegment = '';

    public string $subBusinessSegment = '';

    /**
     * @var array<int, array{name: string}>
     */
    public array $additionalPersons = [];

    /**
     * @return array<string, array<string>>
     */
    public static function getStateCityMap(): array
    {
        return [
            'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot'],
            'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Nashik'],
            'Rajasthan' => ['Jaipur', 'Udaipur', 'Jodhpur', 'Kota'],
            'Madhya Pradesh' => ['Bhopal', 'Indore', 'Gwalior', 'Jabalpur'],
            'Karnataka' => ['Bangalore', 'Mysore', 'Hubli', 'Mangalore'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function getBusinessSegmentMap(): array
    {
        return [
            'Manufacturing' => ['Textiles', 'Chemicals', 'Machinery', 'Food Processing'],
            'Trading' => ['Import/Export', 'Wholesale', 'Retail', 'Distribution'],
            'Services' => ['Consulting', 'Financial Services', 'Logistics', 'Education'],
            'IT & Technology' => ['Software Development', 'IT Services', 'Hardware', 'Telecom'],
            'Healthcare' => ['Pharmaceuticals', 'Medical Devices', 'Hospitals', 'Diagnostics'],
        ];
    }

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibitionId = $exhibition->id;
        $this->source = request()->query('source');
    }

    public function updatedState(): void
    {
        $this->city = '';
    }

    public function updatedBusinessSegment(): void
    {
        $this->subBusinessSegment = '';
    }

    /**
     * @return array<string>
     */
    #[Computed]
    public function cities(): array
    {
        $map = static::getStateCityMap();

        return $map[$this->state] ?? [];
    }

    /**
     * @return array<string>
     */
    #[Computed]
    public function subSegments(): array
    {
        $map = static::getBusinessSegmentMap();

        return $map[$this->businessSegment] ?? [];
    }

    #[Computed]
    public function totalPersons(): int
    {
        return 1 + count($this->additionalPersons);
    }

    #[Computed]
    public function totalAmount(): ?float
    {
        $exhibition = Exhibition::findOrFail($this->exhibitionId);

        if (! $exhibition->isPaidEntry()) {
            return null;
        }

        return (float) $exhibition->entry_amount * $this->totalPersons;
    }

    public function goToStep(int $step): void
    {
        if ($step === 2) {
            $this->validateStep1();
        }

        $this->currentStep = $step;
    }

    public function nextStep(): void
    {
        $this->validateStep1();
        $this->currentStep = 2;
    }

    public function previousStep(): void
    {
        $this->currentStep = 1;
    }

    public function addPerson(): void
    {
        $this->additionalPersons[] = ['name' => ''];
    }

    public function removePerson(int $index): void
    {
        array_splice($this->additionalPersons, $index, 1);
        $this->additionalPersons = array_values($this->additionalPersons);
    }

    public function register(): void
    {
        $this->validateStep1();
        $this->validateStep2();

        $exhibition = Exhibition::findOrFail($this->exhibitionId);

        $additionalPersonsData = array_values(
            array_filter(
                $this->additionalPersons,
                fn(array $person) => ! empty(trim($person['name']))
            )
        );

        $totalAmount = $exhibition->isPaidEntry()
            ? (float) $exhibition->entry_amount * (1 + count($additionalPersonsData))
            : null;

        $visitor = ExhibitionVisitor::create([
            'exhibition_id' => $this->exhibitionId,
            'phone_number' => $this->phoneNumber,
            'name' => $this->name,
            'company_name' => $this->companyName ?: null,
            'designation' => $this->designation ?: null,
            'state' => $this->state,
            'city' => $this->city,
            'email' => $this->email ?: null,
            'business_segment' => $this->businessSegment,
            'sub_business_segment' => $this->subBusinessSegment,
            'additional_persons' => ! empty($additionalPersonsData) ? $additionalPersonsData : null,
            'source' => $this->source,
            'payment_amount' => $totalAmount,
            'status' => $exhibition->isPaidEntry()
                ? VisitorRegistrationStatus::PaymentPending
                : VisitorRegistrationStatus::Confirmed,
        ]);

        if ($exhibition->isPaidEntry()) {
            $this->redirect(
                route('visitor-payment.initiate', ['registrationCode' => $visitor->registration_code]),
                navigate: false
            );
        } else {
            // SMS disabled until DLT template is approved
            // Template ID 1707177157041193630 needs to be active in DLT portal
            // if (config('services.sms.enabled')) {
            //     $passLink = route('visitor.scan', [
            //         'exhibition' => $exhibition->slug,
            //         'registrationCode' => $visitor->registration_code,
            //     ]);
            //
            //     $exhibitionTitle = strlen($exhibition->title) > 20
            //         ? substr($exhibition->title, 0, 17) . '...'
            //         : $exhibition->title;
            //
            //     SendSmsMessage::dispatch(
            //         template: 'visitor_registration_confirmed',
            //         phoneCode: '',
            //         phoneNumber: $visitor->phone_number,
            //         variables: [
            //             explode(' ', trim($visitor->name))[0],  // {#var#} 1 - Name
            //             $passLink,                              // {#var#} 2 - Pass Link
            //         ],
            //         templateId: '1707177157041193630'
            //     );
            // }

            if (config('services.whatsapp.enabled')) {
                $this->sendWhatsAppNotification($visitor, $exhibition);
            }

            $this->redirect(
                route('visitors-registration.thank-you', [
                    'exhibition' => $exhibition,
                    'registrationCode' => $visitor->registration_code,
                ]),
                navigate: true
            );
        }
    }

    private function validateStep1(): void
    {
        $this->validate([
            'phoneNumber' => [
                'required',
                'string',
                'max:20',
                Rule::unique('exhibition_visitors', 'phone_number')
                    ->where('exhibition_id', $this->exhibitionId)
                    ->whereIn('status', [
                        VisitorRegistrationStatus::Confirmed->value,
                    ]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'state' => ['required', 'string', 'in:' . implode(',', array_keys(static::getStateCityMap()))],
            'city' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'businessSegment' => ['required', 'string', 'in:' . implode(',', array_keys(static::getBusinessSegmentMap()))],
            'subBusinessSegment' => ['required', 'string'],
        ], [
            'phoneNumber.required' => 'Please enter your phone number.',
            'phoneNumber.unique' => 'This phone number is already registered for this exhibition.',
            'name.required' => 'Please enter your name.',
            'state.required' => 'Please select a state.',
            'city.required' => 'Please select a city.',
            'businessSegment.required' => 'Please select a business segment.',
            'subBusinessSegment.required' => 'Please select a sub business segment.',
        ]);
    }

    private function validateStep2(): void
    {
        $this->validate([
            'additionalPersons.*.name' => ['required', 'string', 'max:255'],
        ], [
            'additionalPersons.*.name.required' => 'Please enter the name for each additional person.',
            'additionalPersons.*.name.max' => 'Each name may not exceed 255 characters.',
        ]);
    }

    private function sendWhatsAppNotification(ExhibitionVisitor $visitor, Exhibition $exhibition): void
    {
        // Format exhibition dates
        $exhibitionDates = $exhibition->start_date->format('d M Y') . ' to ' . $exhibition->end_date->format('d M Y');

        // Send WhatsApp for primary visitor
        $primaryFirstName = explode(' ', trim($visitor->name))[0];
        $primaryImageUrl = config('app.url') . '/' . $exhibition->slug . '/visitor-pass/' . $visitor->registration_code . '/image';

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
                'Free Entry',                   // {{7}} - Amount
                'N/A',                          // {{8}} - Transaction ID
                $visitor->created_at->format('d-m-Y'), // {{9}} - Payment Date
                $exhibitionDates,               // {{10}} - Exhibition Dates
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $primaryImageUrl,
                'filename' => 'visitor_pass_' . $visitor->registration_code,
            ]
        );

        // Send WhatsApp for each additional person
        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personFirstName = explode(' ', trim($person['name']))[0];
                $personImageUrl = config('app.url') . '/' . $exhibition->slug . '/visitor-pass/' . $visitor->registration_code . '/image?personIndex=' . $index;

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
                        'Free Entry',                   // {{7}} - Amount
                        'N/A',                          // {{8}} - Transaction ID
                        $visitor->created_at->format('d-m-Y'), // {{9}} - Payment Date
                        $exhibitionDates,               // {{10}} - Exhibition Dates
                    ],
                    paramsFallbackValue: [
                        'FirstName' => 'Guest',
                    ],
                    media: [
                        'url' => $personImageUrl,
                        'filename' => 'visitor_pass_' . $visitor->registration_code . '_person_' . ($index + 1),
                    ]
                );
            }
        }
    }

    public function render()
    {
        $exhibition = Exhibition::findOrFail($this->exhibitionId);

        return view('livewire.exhibitions.visitors-registration', [
            'exhibition' => $exhibition,
            'states' => array_keys(static::getStateCityMap()),
            'businessSegments' => array_keys(static::getBusinessSegmentMap()),
        ]);
    }
}
