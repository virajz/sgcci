<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitions;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Services\QrCodeService;
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

    public string $qrCodeSvg = '';

    public string $qrCodeUrl = '';

    public function mount(Exhibition $exhibition, string $registrationCode): void
    {
        $this->exhibitionId = $exhibition->id;
        $this->registrationCode = $registrationCode;

        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();

        // Generate QR code URL
        $this->qrCodeUrl = url("/{$exhibition->slug}/visitors/{$visitor->id}/{$registrationCode}");

        // Generate QR code SVG
        $qrService = app(QrCodeService::class);
        $this->qrCodeSvg = $qrService->generateSvg($this->qrCodeUrl, 300);
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
