<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitor;

use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\ExhibitorLead;
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

    /** @var array{registration_code: string, name: string, phone_number: string, company_name: string|null, designation: string|null, city: string, state: string, status_label: string, status_color: string, is_lead: bool}|null */
    public ?array $foundVisitor = null;

    /** @var array<int, array{registration_code: string, name: string, phone_number: string, company_name: string|null, city: string, state: string, status_label: string, status_color: string}> */
    public array $matchedVisitors = [];

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

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+)\b/i', $trimmed, $matches);
            $trimmed = $matches[1] ?? $trimmed;
        }

        $this->lookupCode = strtoupper($trimmed);
        $this->lookup();
    }

    public function lookup(): void
    {
        $this->validate(['lookupCode' => ['required', 'string']]);

        $term = trim($this->lookupCode);

        if (filter_var($term, FILTER_VALIDATE_URL)) {
            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+)\b/i', $term, $matches);
            $term = $matches[1] ?? $term;
            $this->lookupCode = strtoupper($term);
        }

        $code = strtoupper($term);

        $exhibition = $this->activeExhibition();

        $visitors = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where(function ($q) use ($term, $code): void {
                $q->where('registration_code', $code)
                    ->orWhere('phone_number', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
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
        $isLead = ExhibitorLead::where('booking_id', $this->booking->id)
            ->where('exhibition_visitor_id', $visitor->id)
            ->exists();

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
        ];
    }

    public function markAsLead(): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        ExhibitorLead::firstOrCreate(
            [
                'booking_id' => $this->booking->id,
                'exhibition_visitor_id' => $visitor->id,
            ],
            ['captured_at' => now()]
        );

        $this->setFoundVisitor($visitor);

        Flux::toast(
            heading: 'Lead Saved',
            text: "{$visitor->name} has been marked as a lead.",
            variant: 'success',
        );
    }

    public function resetLookup(): void
    {
        $this->lookupCode = '';
        $this->foundVisitor = null;
        $this->matchedVisitors = [];
        $this->lookupPerformed = false;
    }

    public function render(): \Illuminate\View\View
    {
        $leads = ExhibitorLead::where('booking_id', $this->booking->id)
            ->with('visitor')
            ->orderByDesc('captured_at')
            ->get();

        return view('livewire.exhibitor.leads', [
            'leads' => $leads,
        ]);
    }
}
