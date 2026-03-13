<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitor;

use App\Models\Booking;
use App\Models\CommitteeMember;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\ExhibitorLead;
use App\Models\ExhibitorMemberLead;
use App\Models\Member;
use App\Models\WhatsAppInquiry;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Leads extends Component
{
    public ?Booking $booking = null;

    public string $activeTab = 'capture';

    public string $lookupCode = '';

    public bool $lookupPerformed = false;

    /** @var array{registration_code: string, name: string, phone_number: string, company_name: string|null, designation: string|null, city: string, state: string, status_label: string, status_color: string, is_lead: bool, scanned_person_index: int|null, additional_persons: array<int, array{name: string, phone_number: string}>}|null */
    public ?array $foundVisitor = null;

    /** @var array<int, array{registration_code: string, name: string, phone_number: string, company_name: string|null, city: string, state: string, status_label: string, status_color: string}> */
    public array $matchedVisitors = [];

    /** @var array{membership_number: string, member_type: string, name: string, phone: string|null, post: string|null, is_lead: bool}|null */
    public ?array $foundMember = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user->isExhibitor()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $user->booking;

        if (! $this->booking) {
            abort(404, 'No booking found for this exhibitor.');
        }
    }

    private function activeExhibition(): Exhibition
    {
        return Exhibition::latest()->firstOrFail();
    }

    public function setLookupCode(string $code): void
    {
        $trimmed = trim($code);
        $personIndex = null;

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            // Member scan URL: members/{membershipNumber}/scan
            if (preg_match('#/members/([^/?\s]+)/scan#i', $trimmed, $m)) {
                $this->lookupCode = strtoupper($m[1]);
                $this->lookupMemberByCode(strtoupper($m[1]));

                return;
            }

            $parsed = parse_url($trimmed);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
                if (isset($queryParams['person'])) {
                    $personIndex = (int) $queryParams['person'] - 1;
                }
            }
            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+|PRESS-[A-Z0-9]+|VIP-[A-Z0-9]+|VENDOR-[A-Z0-9]+)\b/i', $trimmed, $matches);
            $trimmed = $matches[1] ?? $trimmed;
        }

        $this->lookupCode = strtoupper($trimmed);
        $this->lookupByCode(strtoupper($trimmed), $personIndex);
    }

    public function lookup(): void
    {
        $this->validate(['lookupCode' => ['required', 'string']]);

        $term = trim($this->lookupCode);

        if (filter_var($term, FILTER_VALIDATE_URL)) {
            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+|PRESS-[A-Z0-9]+|VIP-[A-Z0-9]+|VENDOR-[A-Z0-9]+)\b/i', $term, $matches);
            $term = $matches[1] ?? $term;
            $this->lookupCode = strtoupper($term);
        }

        $this->lookupByCode(strtoupper($term), null);
    }

    private function lookupByCode(string $code, ?int $personIndex): void
    {
        $exhibition = $this->activeExhibition();

        $visitors = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where(function ($q) use ($code): void {
                $q->where('registration_code', $code)
                    ->orWhereRaw('REPLACE(phone_number, \' \', \'\') LIKE ?', ['%'.str_replace(' ', '', $code).'%'])
                    ->orWhereRaw('lower(name) like ?', ['%'.strtolower($code).'%']);
            })
            ->orderBy('name')
            ->get();

        if ($visitors->count() === 1) {
            $this->setFoundVisitor($visitors->first(), $personIndex);
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
            // Fall through to member search if no visitors matched
            $this->lookupMemberByCode($code);

            return;
        }

        $this->foundMember = null;
        $this->lookupPerformed = true;
    }

    public function selectVisitor(string $registrationCode): void
    {
        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $registrationCode)
            ->firstOrFail();

        $this->setFoundVisitor($visitor, null);
        $this->matchedVisitors = [];
    }

    private function setFoundVisitor(ExhibitionVisitor $visitor, ?int $personIndex): void
    {
        $isLead = ExhibitorLead::where('booking_id', $this->booking->id)
            ->where('exhibition_visitor_id', $visitor->id)
            ->where('person_index', $personIndex)
            ->exists();

        $persons = is_array($visitor->additional_persons) ? $visitor->additional_persons : [];

        $additionalPersons = array_map(fn (array $p, int $i) => [
            'name' => $p['name'],
            'phone_number' => $p['phone_number'] ?? '',
            'is_lead' => ExhibitorLead::where('booking_id', $this->booking->id)
                ->where('exhibition_visitor_id', $visitor->id)
                ->where('person_index', $i)
                ->exists(),
        ], $persons, array_keys($persons));

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
            'is_lead' => $isLead,
            'scanned_person_index' => $personIndex,
            'additional_persons' => $additionalPersons,
        ];
    }

    public function markAsLead(): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $personIndex = $this->foundVisitor['scanned_person_index'];
        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        ExhibitorLead::firstOrCreate(
            [
                'booking_id' => $this->booking->id,
                'exhibition_visitor_id' => $visitor->id,
                'person_index' => $personIndex,
            ],
            ['captured_at' => now()]
        );

        $this->setFoundVisitor($visitor, $personIndex);

        $name = $personIndex !== null
            ? ($visitor->additional_persons[$personIndex]['name'] ?? $visitor->name)
            : $visitor->name;

        Flux::toast(
            heading: 'Lead Saved',
            text: "{$name} has been marked as a lead.",
            variant: 'success',
        );
    }

    private function lookupMemberByCode(string $code): void
    {
        $this->foundVisitor = null;
        $this->matchedVisitors = [];
        $this->foundMember = null;

        $committee = CommitteeMember::where('membership_number', $code)->first();
        if ($committee) {
            $this->foundMember = [
                'membership_number' => $committee->membership_number,
                'member_type' => 'committee',
                'name' => $committee->name,
                'phone' => $committee->mobile,
                'post' => $committee->post_for_badge ?? $committee->post,
                'is_lead' => ExhibitorMemberLead::where('booking_id', $this->booking->id)
                    ->where('membership_number', $committee->membership_number)
                    ->exists(),
            ];
            $this->lookupPerformed = true;

            return;
        }

        $member = Member::where('membership_number', $code)->first();
        if ($member) {
            $this->foundMember = [
                'membership_number' => $member->membership_number,
                'member_type' => 'sgcci',
                'name' => $member->contact_name,
                'phone' => $member->cell_no ?? $member->office_phone ?? $member->home_phone,
                'post' => null,
                'is_lead' => ExhibitorMemberLead::where('booking_id', $this->booking->id)
                    ->where('membership_number', $member->membership_number)
                    ->exists(),
            ];
            $this->lookupPerformed = true;

            return;
        }

        $this->lookupPerformed = true;
    }

    public function markAsMemberLead(): void
    {
        if (! $this->foundMember) {
            return;
        }

        ExhibitorMemberLead::firstOrCreate(
            [
                'booking_id' => $this->booking->id,
                'membership_number' => $this->foundMember['membership_number'],
            ],
            [
                'member_type' => $this->foundMember['member_type'],
                'member_name' => $this->foundMember['name'],
                'member_phone' => $this->foundMember['phone'],
                'captured_at' => now(),
            ]
        );

        $this->foundMember['is_lead'] = true;

        Flux::toast(
            heading: 'Lead Saved',
            text: "{$this->foundMember['name']} has been marked as a lead.",
            variant: 'success',
        );
    }

    public function resetLookup(): void
    {
        $this->lookupCode = '';
        $this->foundVisitor = null;
        $this->matchedVisitors = [];
        $this->foundMember = null;
        $this->lookupPerformed = false;
    }

    public function exportWhatsAppInquiries(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $inquiries = WhatsAppInquiry::where('booking_id', $this->booking->id)
            ->orderByDesc('received_at')
            ->get();

        $filename = 'whatsapp-inquiries-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($inquiries): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Name', 'Phone', 'Received At']);

            foreach ($inquiries as $inquiry) {
                fputcsv($handle, [
                    $inquiry->name ?? '',
                    $inquiry->phone_number,
                    $inquiry->received_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportLeads(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $leads = ExhibitorLead::where('booking_id', $this->booking->id)
            ->with('visitor')
            ->orderByDesc('captured_at')
            ->get();

        $filename = 'leads-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($leads): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Name', 'Phone', 'Company', 'Designation', 'City', 'State', 'Pass Code', 'Captured At']);

            foreach ($leads as $lead) {
                $persons = is_array($lead->visitor->additional_persons) ? $lead->visitor->additional_persons : [];
                $person = $lead->person_index !== null ? ($persons[$lead->person_index] ?? null) : null;

                fputcsv($handle, [
                    $person ? $person['name'] : $lead->visitor->name,
                    $person ? ($person['phone_number'] ?? '') : $lead->visitor->phone_number,
                    $lead->visitor->company_name ?? '',
                    $person ? '' : ($lead->visitor->designation ?? ''),
                    $lead->visitor->city,
                    $lead->visitor->state,
                    $lead->visitor->registration_code,
                    $lead->captured_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render(): \Illuminate\View\View
    {
        $leads = ExhibitorLead::where('booking_id', $this->booking->id)
            ->with('visitor')
            ->orderByDesc('captured_at')
            ->get();

        $memberLeads = ExhibitorMemberLead::where('booking_id', $this->booking->id)
            ->orderByDesc('captured_at')
            ->get();

        $whatsAppInquiries = WhatsAppInquiry::where('booking_id', $this->booking->id)
            ->orderByDesc('received_at')
            ->get();

        return view('livewire.exhibitor.leads', [
            'leads' => $leads,
            'memberLeads' => $memberLeads,
            'whatsAppInquiries' => $whatsAppInquiries,
        ]);
    }
}
