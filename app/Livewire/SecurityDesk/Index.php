<?php

declare(strict_types=1);

namespace App\Livewire\SecurityDesk;

use App\Models\CommitteeMember;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\Member;
use App\Models\MemberScan;
use App\VisitorRegistrationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.security-desk')]
class Index extends Component
{
    public string $lookupCode = '';

    public bool $lookupPerformed = false;

    /** @var array{registration_code: string, name: string, phone_number: string, company_name: string|null, designation: string|null, city: string, state: string, status_label: string, status_color: string, is_paid: bool, is_invited_guest: bool, entered_at: string|null, exited_at: string|null, additional_persons: array<int, array{name: string, phone_number: string, entered_at: string|null, exited_at: string|null}>, exhibition_title: string, exhibition_logo_url: string|null}|null */
    public ?array $foundVisitor = null;

    /** @var array<int, array{registration_code: string, name: string, phone_number: string, company_name: string|null, city: string, state: string, status_label: string, status_color: string, exhibition_title: string, exhibition_logo_url: string|null}> */
    public array $matchedVisitors = [];

    /**
     * @var array<int, array{id: string, type: string, name: string, code: string, time: string, entered_at: string|null, duration: int}>
     *
     * type: 'entered' | 're_entered' | 'already_entered' | 'not_found'
     */
    public array $scanLog = [];

    public function mount(Request $request): void
    {
        if ($request->query('lookup')) {
            $personIndex = $request->filled('person') ? (int) $request->query('person') : null;
            $this->setLookupCode((string) $request->query('lookup'), $personIndex);
        }
    }

    /**
     * @return Collection<int, Exhibition>
     */
    #[Computed(persist: true)]
    public function openExhibitions(): Collection
    {
        return Exhibition::query()
            ->where('registration_closed', false)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * @return Collection<int, int>
     */
    private function openExhibitionIds(): Collection
    {
        return $this->openExhibitions->pluck('id');
    }

    public function setLookupCode(string $code, ?int $personIndex = null): void
    {
        $trimmed = trim($code);

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            // Member scan URL: /members/{membershipNumber}/scan
            if (preg_match('#/members/([^/?]+)/scan#', $trimmed, $memberMatches)) {
                $this->scanAndEnterMember($memberMatches[1]);

                return;
            }

            if ($personIndex === null) {
                $parsed = parse_url($trimmed);
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (isset($queryParams['person'])) {
                        $personIndex = (int) $queryParams['person'];
                    }
                }
            }

            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+|PRESS-[A-Z0-9]+|VIP-[A-Z0-9]+|VENDOR-[A-Z0-9]+)\b/i', $trimmed, $matches);
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

        $visitor = ExhibitionVisitor::with('exhibition')
            ->whereIn('exhibition_id', $this->openExhibitionIds())
            ->where('registration_code', $code)
            ->first();

        if (! $visitor) {
            $this->addScanLog('not_found', $code, $code, 5);
            $this->resetForNextScan();

            return;
        }

        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && preg_match('/^(VIS|PRESS|VIP|VENDOR)-/', $visitor->registration_code)
            && $visitor->invited_by_booking_id === null;

        if (! $isPaidVisitor) {
            $this->addScanLog('not_found', $visitor->name, $code, 5, null, null, false, $visitor->exhibition?->title, $visitor->exhibition?->logo_url);
            $this->resetForNextScan();

            return;
        }

        $this->attemptEntry($visitor, $personIndex);
        $this->resetForNextScan();
    }

    /**
     * Called when a member QR code is scanned — marks entry in member_scans and logs result.
     */
    private function scanAndEnterMember(string $membershipNumber): void
    {
        $committeeMember = CommitteeMember::where('membership_number', $membershipNumber)->first();
        $sgcciMember = $committeeMember === null
            ? Member::where('membership_number', $membershipNumber)->first()
            : null;

        if (! $committeeMember && ! $sgcciMember) {
            $this->addScanLog('not_found', $membershipNumber, $membershipNumber, 5);
            $this->resetForNextScan();

            return;
        }

        if ($committeeMember) {
            $memberName = $committeeMember->name;
            $memberType = 'committee';
            $memberSub = ($committeeMember->post_for_badge ?? $committeeMember->post ?? 'Organizer').' · '.$membershipNumber;
        } else {
            $memberName = $sgcciMember->contact_name;
            $memberType = 'sgcci';
            $memberSub = 'SGCCI Member · '.$membershipNumber;
        }

        /** @var MemberScan|null $scan */
        $scan = MemberScan::where('membership_number', $membershipNumber)
            ->whereDate('entered_at', today())
            ->first();

        if ($scan && $scan->entered_at && ! $scan->exited_at) {
            $this->addScanLog('already_entered', $memberName, $membershipNumber, 30, $scan->entered_at->format('d M Y, h:i A'), $memberSub, true);
            $this->resetForNextScan();

            return;
        }

        $isReEntry = $scan && $scan->entered_at && $scan->exited_at;

        if ($scan) {
            $scan->update(['entered_at' => now(), 'exited_at' => null]);
        } else {
            MemberScan::create([
                'membership_number' => $membershipNumber,
                'member_type' => $memberType,
                'member_name' => $memberName,
                'entered_at' => now(),
            ]);
        }

        $this->addScanLog($isReEntry ? 're_entered' : 'entered', $memberName, $membershipNumber, 5, null, $memberSub, true);
        $this->resetForNextScan();
    }

    /**
     * Used by manual lookup — marks entry if applicable but keeps the visitor card visible.
     */
    private function maybeEnterOnLookup(ExhibitionVisitor &$visitor): void
    {
        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && preg_match('/^(VIS|PRESS|VIP|VENDOR)-/', $visitor->registration_code)
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
        $exhibitionTitle = $visitor->exhibition?->title;
        $exhibitionLogoUrl = $visitor->exhibition?->logo_url;

        if ($personIndex === null) {
            // ── Primary visitor ────────────────────────────────────────────────
            $displayName = $visitor->name;

            if ($visitor->entered_at) {
                $this->addScanLog('already_entered', $displayName, $visitor->registration_code, 30, $visitor->entered_at->format('d M Y, h:i A'), null, false, $exhibitionTitle, $exhibitionLogoUrl);

                return;
            }

            $visitor->update(['entered_at' => now(), 'exited_at' => null]);
            $this->addScanLog('entered', $displayName, $visitor->registration_code, 5, null, null, false, $exhibitionTitle, $exhibitionLogoUrl);

        } else {
            // ── Additional person (1-based index) ──────────────────────────────
            $arrayIndex = $personIndex - 1;
            $person = $persons[$arrayIndex] ?? null;

            if (! $person) {
                $this->addScanLog('not_found', "Person #{$personIndex} of {$visitor->name}", $visitor->registration_code, 5, null, null, false, $exhibitionTitle, $exhibitionLogoUrl);

                return;
            }

            $displayName = $person['name'];

            $personEnteredAt = isset($person['entered_at']) ? Carbon::parse($person['entered_at']) : null;

            if ($personEnteredAt) {
                $this->addScanLog('already_entered', $displayName, $visitor->registration_code, 30, $personEnteredAt->format('d M Y, h:i A'), null, false, $exhibitionTitle, $exhibitionLogoUrl);

                return;
            }

            $persons[$arrayIndex]['entered_at'] = now()->toIso8601String();
            $persons[$arrayIndex]['exited_at'] = null;
            $visitor->update(['additional_persons' => $persons]);

            $this->addScanLog('entered', $displayName, $visitor->registration_code, 5, null, null, false, $exhibitionTitle, $exhibitionLogoUrl);
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

        $visitors = ExhibitionVisitor::with('exhibition')
            ->whereIn('exhibition_id', $this->openExhibitionIds())
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
            $this->setFoundVisitor($visitor->fresh()->load('exhibition'));
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
                'exhibition_title' => $v->exhibition?->title ?? '',
                'exhibition_logo_url' => $v->exhibition?->logo_url,
            ])->values()->all();
        } else {
            // No visitor found — try members (marks entry and logs result)
            $this->lookupCode = $code;
            $this->scanAndEnterMember($code);

            return;
        }

        $this->lookupCode = '';
        $this->lookupPerformed = true;
    }

    public function selectVisitor(string $registrationCode): void
    {
        $visitor = ExhibitionVisitor::with('exhibition')
            ->whereIn('exhibition_id', $this->openExhibitionIds())
            ->where('registration_code', $registrationCode)
            ->firstOrFail();

        $this->maybeEnterOnLookup($visitor);
        $this->setFoundVisitor($visitor->fresh()->load('exhibition'));
        $this->matchedVisitors = [];
    }

    private function setFoundVisitor(ExhibitionVisitor $visitor): void
    {
        $visitor->loadMissing('exhibition');

        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && preg_match('/^(VIS|PRESS|VIP|VENDOR)-/', $visitor->registration_code)
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
            'exhibition_title' => $visitor->exhibition?->title ?? '',
            'exhibition_logo_url' => $visitor->exhibition?->logo_url,
        ];
    }

    public function markExited(): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $visitor = ExhibitionVisitor::whereIn('exhibition_id', $this->openExhibitionIds())
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        $visitor->update(['exited_at' => now()]);

        $this->setFoundVisitor($visitor->fresh()->load('exhibition'));
    }

    public function markPersonExited(int $personIndex): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $visitor = ExhibitionVisitor::whereIn('exhibition_id', $this->openExhibitionIds())
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        $persons = is_array($visitor->additional_persons) ? $visitor->additional_persons : [];
        $arrayIndex = $personIndex - 1;

        if (! isset($persons[$arrayIndex])) {
            return;
        }

        $persons[$arrayIndex]['exited_at'] = now()->toIso8601String();
        $visitor->update(['additional_persons' => $persons]);

        $this->setFoundVisitor($visitor->fresh()->load('exhibition'));
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
        $this->dispatch('refocus-search');
    }

    private function addScanLog(string $type, string $name, string $code, int $duration, ?string $enteredAt = null, ?string $sub = null, bool $isMember = false, ?string $exhibitionTitle = null, ?string $exhibitionLogoUrl = null): void
    {
        $entry = [
            'id' => uniqid(),
            'type' => $type,
            'name' => $name,
            'code' => $code,
            'sub' => $sub,
            'is_member' => $isMember,
            'time' => now()->format('h:i:s A'),
            'entered_at' => $enteredAt,
            'duration' => $duration,
            'exhibition_title' => $exhibitionTitle,
            'exhibition_logo_url' => $exhibitionLogoUrl,
        ];

        array_unshift($this->scanLog, $entry);

        $this->dispatch('scan-result', entry: $entry);

        $this->scanLog = array_slice($this->scanLog, 0, 20);
    }

    public function render(): View
    {
        return view('livewire.security-desk.index');
    }
}
