<?php

declare(strict_types=1);

namespace App\Livewire\SecurityDesk;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.security-desk')]
class Index extends Component
{
    public string $lookupCode = '';

    public bool $lookupPerformed = false;

    /** @var array{registration_code: string, name: string, phone_number: string, company_name: string|null, designation: string|null, city: string, state: string, status_label: string, status_color: string, is_paid: bool, is_invited_guest: bool, entered_at: string|null, exited_at: string|null, additional_persons: array<int, array{name: string, phone_number: string}>}|null */
    public ?array $foundVisitor = null;

    /** @var array<int, array{registration_code: string, name: string, phone_number: string, company_name: string|null, city: string, state: string, status_label: string, status_color: string}> */
    public array $matchedVisitors = [];

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

    public function setLookupCode(string $code): void
    {
        $this->lookupCode = strtoupper(trim($code));
        $this->lookup();
    }

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
        $isPaidVisitor = $visitor->status === VisitorRegistrationStatus::Confirmed
            && str_starts_with($visitor->registration_code, 'VIS-')
            && $visitor->invited_by_booking_id === null;

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
            'additional_persons' => is_array($visitor->additional_persons) ? $visitor->additional_persons : [],
        ];
    }

    public function markEntered(): void
    {
        if (! $this->foundVisitor) {
            return;
        }

        $exhibition = $this->activeExhibition();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $this->foundVisitor['registration_code'])
            ->firstOrFail();

        $visitor->update(['entered_at' => now(), 'exited_at' => null]);

        $this->setFoundVisitor($visitor->fresh());

        Flux::toast(
            heading: 'Entry Marked',
            text: "{$visitor->name} has been marked as entered.",
            variant: 'success',
        );
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

        Flux::toast(
            heading: 'Exit Marked',
            text: "{$visitor->name} has been marked as exited.",
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
        return view('livewire.security-desk.index');
    }
}
