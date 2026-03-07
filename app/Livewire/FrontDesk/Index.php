<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Livewire\Exhibitions\VisitorsRegistration;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.front-desk')]
class Index extends Component
{
    // ── Tab ──────────────────────────────────────────────────────────────────
    public string $activeTab = 'lookup';

    // ── Lookup ────────────────────────────────────────────────────────────────
    public string $lookupCode = '';

    public bool $lookupPerformed = false;

    /** @var array{registration_code: string, name: string, phone_number: string, company_name: string|null, designation: string|null, city: string, state: string, status_label: string, status_color: string, additional_persons: array<int, array{name: string, phone_number: string}>}|null */
    public ?array $foundVisitor = null;

    /** @var array<int, array{registration_code: string, name: string, phone_number: string, company_name: string|null, city: string, state: string, status_label: string, status_color: string}> */
    public array $matchedVisitors = [];

    // ── Add Visitor ───────────────────────────────────────────────────────────
    public string $phoneNumber = '';

    public string $name = '';

    public string $companyName = '';

    public string $designation = '';

    public string $state = '';

    public string $city = '';

    public string $email = '';

    public string $segment = '';

    /** @var array<int, array{name: string, phone_number: string}> */
    public array $additionalPersons = [];

    public bool $addSuccess = false;

    public string $addedRegistrationCode = '';

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(\Illuminate\Http\Request $request): void
    {
        if ($request->query('lookup')) {
            $this->setLookupCode((string) $request->query('lookup'));
        }
    }

    private function activeExhibition(): Exhibition
    {
        return Exhibition::latest()->firstOrFail();
    }

    public function updatedState(): void
    {
        $this->city = '';
    }

    public function setLookupCode(string $code): void
    {
        $this->lookupCode = strtoupper(trim($code));
        $this->lookup();
    }

    /** @return array<string> */
    public function getCitiesProperty(): array
    {
        $map = VisitorsRegistration::getStateCityMap();

        return $map[$this->state] ?? [];
    }

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function lookup(): void
    {
        $this->validate(['lookupCode' => ['required', 'string']]);

        $term = trim($this->lookupCode);
        $code = strtoupper($term);

        $exhibition = $this->activeExhibition();

        $visitors = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where(function ($q) use ($term, $code): void {
                $q->where('registration_code', $code)
                    ->orWhere('phone_number', 'like', "%{$term}%")
                    ->orWhere('name', 'ilike', "%{$term}%");
            })
            ->orderBy('name')
            ->get();

        if ($visitors->count() === 1) {
            $this->setFoundVisitor($visitors->first());
            $this->matchedVisitors = [];
        } elseif ($visitors->count() > 1) {
            $this->foundVisitor = null;
            $this->matchedVisitors = $visitors->map(fn (ExhibitionVisitor $v) => [
                'registration_code' => $v->registration_code,
                'name' => $v->name,
                'phone_number' => $v->phone_number,
                'company_name' => $v->company_name,
                'city' => $v->city,
                'state' => $v->state,
                'status_label' => $v->status->label(),
                'status_color' => $v->status->color(),
            ])->values()->all();
        } else {
            $this->foundVisitor = null;
            $this->matchedVisitors = [];
        }

        $this->lookupPerformed = true;
    }

    public function selectVisitor(string $registrationCode): void
    {
        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $registrationCode)
            ->firstOrFail();

        $this->setFoundVisitor($visitor);
        $this->matchedVisitors = [];
    }

    private function setFoundVisitor(ExhibitionVisitor $visitor): void
    {
        $this->foundVisitor = [
            'registration_code' => $visitor->registration_code,
            'name' => $visitor->name,
            'phone_number' => $visitor->phone_number,
            'company_name' => $visitor->company_name,
            'designation' => $visitor->designation,
            'city' => $visitor->city,
            'state' => $visitor->state,
            'status_label' => $visitor->status->label(),
            'status_color' => $visitor->status->color(),
            'additional_persons' => is_array($visitor->additional_persons) ? $visitor->additional_persons : [],
        ];
    }

    public function resetLookup(): void
    {
        $this->lookupCode = '';
        $this->foundVisitor = null;
        $this->matchedVisitors = [];
        $this->lookupPerformed = false;
    }

    // ── Add Visitor ───────────────────────────────────────────────────────────

    public function addPerson(): void
    {
        $this->additionalPersons[] = ['name' => '', 'phone_number' => ''];
    }

    public function removePerson(int $index): void
    {
        array_splice($this->additionalPersons, $index, 1);
        $this->additionalPersons = array_values($this->additionalPersons);
    }

    public function registerWalkIn(): void
    {
        $exhibition = $this->activeExhibition();

        $this->validate($this->addRules($exhibition->id), $this->addMessages());

        $additionalPersonsData = array_values(
            array_filter(
                $this->additionalPersons,
                fn (array $person) => ! empty(trim($person['name']))
            )
        );

        $visitor = ExhibitionVisitor::create([
            'exhibition_id' => $exhibition->id,
            'phone_number' => $this->phoneNumber,
            'name' => $this->name,
            'company_name' => $this->companyName ?: null,
            'designation' => $this->designation ?: null,
            'state' => $this->state,
            'city' => $this->city,
            'email' => $this->email ?: null,
            'business_segment' => $this->segment ?: null,
            'additional_persons' => ! empty($additionalPersonsData) ? $additionalPersonsData : null,
            'source' => 'front_desk',
            'status' => VisitorRegistrationStatus::Confirmed,
        ]);

        $this->addedRegistrationCode = $visitor->registration_code;
        $this->addSuccess = true;
        $this->resetAddForm(preserveSuccessState: true);

        Flux::toast(
            heading: 'Visitor Registered!',
            variant: 'success',
            text: "Walk-in visitor {$visitor->name} registered as {$visitor->registration_code}."
        );
    }

    public function startNewRegistration(): void
    {
        $this->addSuccess = false;
        $this->addedRegistrationCode = '';
    }

    private function resetAddForm(bool $preserveSuccessState = false): void
    {
        $this->phoneNumber = '';
        $this->name = '';
        $this->companyName = '';
        $this->designation = '';
        $this->state = '';
        $this->city = '';
        $this->email = '';
        $this->segment = '';
        $this->additionalPersons = [];

        if (! $preserveSuccessState) {
            $this->addSuccess = false;
            $this->addedRegistrationCode = '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function addRules(int $exhibitionId): array
    {
        $allFormPhones = array_filter(
            array_map(fn (array $p) => trim($p['phone_number'] ?? ''), $this->additionalPersons)
        );

        return [
            'phoneNumber' => [
                'required', 'string', 'max:20',
                Rule::unique('exhibition_visitors', 'phone_number')
                    ->where('exhibition_id', $exhibitionId)
                    ->whereIn('status', [VisitorRegistrationStatus::Confirmed->value]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'state' => ['required', 'string', 'in:'.implode(',', array_keys(VisitorsRegistration::getStateCityMap()))],
            'city' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'segment' => ['nullable', 'string', 'in:Business,Job (Working Professional),Student,Housewife,Other'],
            'additionalPersons.*.name' => ['required', 'string', 'max:255'],
            'additionalPersons.*.phone_number' => [
                'required', 'string', 'max:20',
                function (string $_attribute, mixed $value, \Closure $fail) use ($allFormPhones, $exhibitionId): void {
                    $phone = trim((string) $value);

                    if ($phone === trim($this->phoneNumber)) {
                        $fail('This phone number is already used as the primary visitor.');

                        return;
                    }

                    if (count(array_keys($allFormPhones, $phone)) > 1) {
                        $fail('Each additional person must have a unique phone number.');

                        return;
                    }

                    $alreadyRegistered = ExhibitionVisitor::query()
                        ->where('exhibition_id', $exhibitionId)
                        ->whereIn('status', [VisitorRegistrationStatus::Confirmed->value])
                        ->where(function ($q) use ($phone): void {
                            $q->where('phone_number', $phone)
                                ->orWhereRaw(
                                    "EXISTS (SELECT 1 FROM jsonb_array_elements(additional_persons::jsonb) AS p WHERE p->>'phone_number' = ?)",
                                    [$phone]
                                );
                        })
                        ->exists();

                    if ($alreadyRegistered) {
                        $fail('This phone number is already registered for this exhibition.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function addMessages(): array
    {
        return [
            'phoneNumber.required' => 'Please enter a phone number.',
            'phoneNumber.unique' => 'This phone number is already registered for this exhibition.',
            'name.required' => 'Please enter a name.',
            'state.required' => 'Please select a state.',
            'city.required' => 'Please select a city.',
            'additionalPersons.*.name.required' => 'Please enter the name for each additional person.',
            'additionalPersons.*.phone_number.required' => 'Please enter the phone number for each additional person.',
        ];
    }

    public function render(): \Illuminate\View\View
    {
        $states = array_keys(VisitorsRegistration::getStateCityMap());
        $cities = $this->getCitiesProperty();

        return view('livewire.front-desk.index', compact('states', 'cities'));
    }
}
