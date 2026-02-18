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

    /**
     * @var array<int, array{name: string, qrCodeSvg: string, downloadUrl: string}>
     */
    public array $additionalPersonPasses = [];

    public function mount(Exhibition $exhibition, string $registrationCode): void
    {
        $this->exhibitionId = $exhibition->id;
        $this->registrationCode = $registrationCode;

        $visitor = ExhibitionVisitor::where('registration_code', $registrationCode)
            ->where('exhibition_id', $exhibition->id)
            ->firstOrFail();

        $qrService = app(QrCodeService::class);

        // Primary visitor: QR encodes the smart scan URL
        $this->qrCodeUrl = route('visitor.scan', [
            'exhibition' => $exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);
        $this->qrCodeSvg = $qrService->generateSvg($this->qrCodeUrl, 300);

        // Additional persons: each gets their own QR with a person index
        $this->additionalPersonPasses = [];

        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personQrUrl = route('visitor.scan', [
                    'exhibition' => $exhibition->slug,
                    'registrationCode' => $registrationCode,
                ]).'?person='.($index + 1);

                $this->additionalPersonPasses[] = [
                    'name' => $person['name'],
                    'qrCodeSvg' => $qrService->generateSvg($personQrUrl, 300),
                    'downloadUrl' => route('visitor-pass.download', [
                        'exhibition' => $exhibition->slug,
                        'registrationCode' => $registrationCode,
                        'personIndex' => $index,
                    ]),
                ];
            }
        }
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
