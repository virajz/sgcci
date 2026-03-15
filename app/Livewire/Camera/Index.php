<?php

declare(strict_types=1);

namespace App\Livewire\Camera;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.camera')]
class Index extends Component
{
    public function setCode(string $raw): void
    {
        $trimmed = trim($raw);

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            preg_match('/\b((?:IN)?VIS-[A-Z0-9]+|PRESS-[A-Z0-9]+|VIP-[A-Z0-9]+|VENDOR-[A-Z0-9]+)\b/i', $trimmed, $matches);
            $trimmed = $matches[1] ?? $trimmed;
        }

        $code = strtoupper($trimmed);

        $exhibition = Exhibition::latest()->firstOrFail();

        $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('registration_code', $code)
            ->where('status', VisitorRegistrationStatus::Confirmed)
            ->first();

        if (! $visitor) {
            $this->dispatch('code-result', found: false, code: $code, label: null, color: null);

            return;
        }

        [$label, $color] = $this->resolveLabel($visitor->registration_code);

        $this->dispatch('code-result', found: true, code: $visitor->registration_code, label: $label, color: $color, name: $visitor->name);
    }

    /**
     * @return array{string, string}
     */
    private function resolveLabel(string $registrationCode): array
    {
        if (str_starts_with($registrationCode, 'INVIS-')) {
            return ['INVIS', 'indigo'];
        }
        if (str_starts_with($registrationCode, 'VIS-')) {
            return ['VIS', 'blue'];
        }
        if (str_starts_with($registrationCode, 'PRESS-')) {
            return ['PRESS', 'purple'];
        }
        if (str_starts_with($registrationCode, 'VIP-')) {
            return ['VIP', 'amber'];
        }
        if (str_starts_with($registrationCode, 'VENDOR-')) {
            return ['VENDOR', 'green'];
        }

        return ['VISITOR', 'blue'];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.camera.index');
    }
}
