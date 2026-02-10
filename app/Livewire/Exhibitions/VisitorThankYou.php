<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitions;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.front')]
class VisitorThankYou extends Component
{
    #[Locked]
    public string $registrationCode;

    #[Locked]
    public int $exhibitionId;

    public function mount(Exhibition $exhibition, string $registrationCode): void
    {
        $this->exhibitionId = $exhibition->id;
        $this->registrationCode = $registrationCode;
    }

    public function render()
    {
        $exhibition = Exhibition::findOrFail($this->exhibitionId);
        $visitor = ExhibitionVisitor::where('registration_code', $this->registrationCode)
            ->where('exhibition_id', $this->exhibitionId)
            ->firstOrFail();

        return view('livewire.exhibitions.visitor-thank-you', [
            'exhibition' => $exhibition,
            'visitor' => $visitor,
        ]);
    }
}
