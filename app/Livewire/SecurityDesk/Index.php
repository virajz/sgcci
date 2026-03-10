<?php

declare(strict_types=1);

namespace App\Livewire\SecurityDesk;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.security-desk')]
class Index extends Component
{
    public string $lookupCode = '';

    public bool $lookupPerformed = false;

    /** @var array{registration_code: string, name: string, phone_number: string, company_name: string|null, designation: string|null, city: string, state: string, status_label: string, status_color: string, is_paid: bool, is_invited_guest: bool, entered_at: string|null, exited_at: string|null, additional_persons: array<int, array{name: string, phone_number: string, entered_at: string|null, exited_at: string|null}>}|null */
    public ?array $foundVisitor = null;

    /** @var array<int, array{registration_code: string, name: string, phone_number: string, company_name: string|null, city: string, state: string, status_label: string, status_color: string}> */
    public array $matchedVisitors = [];

    /**
     * @var array<int, array{id: string, type: string, name: string, code: string, time: string, entered_at: string|null, duration: int}>
     *
     * type: 'entered' | 're_entered' | 'already_entered' | 'not_found'
     */
    public array $scanLog = [];

    public function mount(\Illuminate\Http\Request $request): void
    {
        if ($request->query('lookup')) {
            $personIndex = $request->filled('person') ? (int) $request->query('person') : null;
            $this->setLookupCode((string) $request->query('lookup'), $personIndex);
        }
    }

    private function activeExhibition(): Exhibition
    {
        return Exhibition::latest()->firstOrFail();
    }

    public function setLookupCode(string $code, ?int $personIndex = null): void
    {
        $trimmed = trim($code);

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            if ($personIndex === null) {
                $parsed = parse_url($trimmed);
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (isset($queryParams['person'])) {
                        $personIndex = (int) $queryParams['person'];
                    }
                }
            }

            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+)\b/i', $trimmed, $matches);
            $trimmed = $matches[1] ?? $trimmed;
        }

        $this->lookupCode = strtoupper($trimmed);
        $this->scanAndEnter($personIndex);
    }

    /**
     * Called when the scanner sends a code — auto-marks entry for the specific person
     * and logs the result, then resets state so the scanner is ready for the next scan.
     */
    public function scanAndEnter(?int $personIndex = null): void
    {
        $this->validate(['lookupCode' => ['required', 'string']]);

        $code = trim($this->lookupCode);
        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $code)
            ->first();

        if (! $visitor) {
            $this->addScanLog('not_found', $code, $code, 5);
            $this->resetForNextScan();

            return;
        }

        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && str_starts_with($visitor->registration_code, 'VIS-')
            && $visitor->invited_by_booking_id === null;

        if (! $isPaidVisitor) {
            $this->addScanLog('not_found', $visitor->name, $code, 5);
            $this->resetForNextScan();

            return;
        }

        $this->attemptEntry($visitor, $personIndex);
        $this->resetForNextScan();
    }

    /**
     * Used by manual lookup — marks entry if applicable but keeps the visitor card visible.
     */
    private function maybeEnterOnLookup(ExhibitionVisitor &$visitor): void
    {
        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && str_starts_with($visitor->registration_code, 'VIS-')
            && $visitor->invited_by_booking_id === null;

        if (! $isPaidVisitor) {
            return;
        }

        // On manual lookup we enter the primary visitor only (no ?person context)
        $this->attemptEntry($visitor, null);
        $visitor = $visitor->fresh();
    }

    /**
     * Attempt entry for a specific person (null = primary visitor, 1+ = additional person).
     * Tracks entry independently per person in the additional_persons JSON array.
     */
    private function attemptEntry(ExhibitionVisitor $visitor, ?int $personIndex): void
    {
        $persons = is_array($visitor->additional_persons) ? $visitor->additional_persons : [];

        if ($personIndex === null) {
            // ── Primary visitor ────────────────────────────────────────────────
            $displayName = $visitor->name;

            if ($visitor->entered_at && ! $visitor->exited_at) {
                $this->addScanLog('already_entered', $displayName, $visitor->registration_code, 30, $visitor->entered_at->format('d M Y, h:i A'));

                return;
            }

            $isReEntry = $visitor->entered_at && $visitor->exited_at;
            $visitor->update(['entered_at' => now(), 'exited_at' => null]);
            $this->addScanLog($isReEntry ? 're_entered' : 'entered', $displayName, $visitor->registration_code, 5);

        } else {
            // ── Additional person (1-based index) ──────────────────────────────
            $arrayIndex = $personIndex - 1;
            $person = $persons[$arrayIndex] ?? null;

            if (! $person) {
                $this->addScanLog('not_found', "Person #{$personIndex} of {$visitor->name}", $visitor->registration_code, 5);

                return;
            }

            $displayName = $person['name'];

            $personEnteredAt = isset($person['entered_at']) ? Carbon::parse($person['entered_at']) : null;
            $personExitedAt = isset($person['exited_at']) ? Carbon::parse($person['exited_at']) : null;

            if ($personEnteredAt && ! $personExitedAt) {
                $this->addScanLog('already_entered', $displayName, $visitor->registration_code, 30, $personEnteredAt->format('d M Y, h:i A'));

                return;
            }

            $isReEntry = $personEnteredAt && $personExitedAt;

            $persons[$arrayIndex]['entered_at'] = now()->toIso8601String();
            $persons[$arrayIndex]['exited_at'] = null;
            $visitor->update(['additional_persons' => $persons]);

            $this->addScanLog($isReEntry ? 're_entered' : 'entered', $displayName, $visitor->registration_code, 5);
        }
    }

    public function lookup(): void
    {
        $this->validate(['lookupCode' => ['required', 'string']]);

        $term = trim($this->lookupCode);

        if (filter_var($term, FILTER_VALIDATE_URL)) {
            // Scanner sent a full URL — delegate to setLookupCode which handles
            // both code extraction and ?person=N parsing, then treat as a scan.
            $this->setLookupCode($term);

            return;
        }

        $code = strtoupper($term);

        $exhibition = $this->activeExhibition();

        $visitors = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where(function ($q) use ($term, $code): void {
                $q->where('registration_code', $code)
                    ->orWhere('phone_number', 'like', "%{$term}%")
                    ->orWhereRaw('lower(name) like ?', ['%'.strtolower($term).'%']);
            })
            ->orderBy('name')
            ->get();

        if ($visitors->count() === 1) {
            $visitor = $visitors->first();
            $this->maybeEnterOnLookup($visitor);
            $this->setFoundVisitor($visitor->fresh());
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

        $this->lookupCode = '';
        $this->lookupPerformed = true;
    }

    public function selectVisitor(string $registrationCode): void
    {
        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $registrationCode)
            ->firstOrFail();

        $this->maybeEnterOnLookup($visitor);
        $this->setFoundVisitor($visitor->fresh());
        $this->matchedVisitors = [];
    }

    private function setFoundVisitor(ExhibitionVisitor $visitor): void
    {
        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && str_starts_with($visitor->registration_code, 'VIS-')
            && $visitor->invited_by_booking_id === null;

        $persons = is_array($visitor->additional_persons) ? $visitor->additional_persons : [];

        $additionalPersons = array_map(fn (array $p) => [
            'name' => $p['name'],
            'phone_number' => $p['phone_number'] ?? '',
            'entered_at' => isset($p['entered_at'])
                ? Carbon::parse($p['entered_at'])->format('d M Y, h:i A')
                : null,
            'exited_at' => isset($p['exited_at'])
                ? Carbon::parse($p['exited_at'])->format('d M Y, h:i A')
                : null,
        ], $persons);

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
            'is_paid' => $isPaidVisitor,
            'is_invited_guest' => $visitor->invited_by_booking_id !== null,
            'entered_at' => $visitor->entered_at?->format('d M Y, h:i A'),
            'exited_at' => $visitor->exited_at?->format('d M Y, h:i A'),
            'additional_persons' => $additionalPersons,
        ];
    }

    public function markExited(): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        $visitor->update(['exited_at' => now()]);

        $this->setFoundVisitor($visitor->fresh());
    }

    public function markPersonExited(int $personIndex): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        $persons = is_array($visitor->additional_persons) ? $visitor->additional_persons : [];
        $arrayIndex = $personIndex - 1;

        if (! isset($persons[$arrayIndex])) {
            return;
        }

        $persons[$arrayIndex]['exited_at'] = now()->toIso8601String();
        $visitor->update(['additional_persons' => $persons]);

        $this->setFoundVisitor($visitor->fresh());
    }

    public function dismissLog(string $id): void
    {
        $this->scanLog = array_values(
            array_filter($this->scanLog, fn (array $entry) => $entry['id'] !== $id)
        );
    }

    public function resetLookup(): void
    {
        $this->lookupCode = '';
        $this->foundVisitor = null;
        $this->matchedVisitors = [];
        $this->lookupPerformed = false;
    }

    private function resetForNextScan(): void
    {
        $this->lookupCode = '';
        $this->foundVisitor = null;
        $this->matchedVisitors = [];
        $this->lookupPerformed = false;
    }

    private function addScanLog(string $type, string $name, string $code, int $duration, ?string $enteredAt = null): void
    {
        array_unshift($this->scanLog, [
            'id' => uniqid(),
            'type' => $type,
            'name' => $name,
            'code' => $code,
            'time' => now()->format('h:i:s A'),
            'entered_at' => $enteredAt,
            'duration' => $duration,
        ]);

        $this->scanLog = array_slice($this->scanLog, 0, 20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.security-desk.index');
    }
}
