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

    public string $passImageDataUri = '';

    /**
     * @var array<int, array{name: string, passImageDataUri: string, downloadUrl: string}>
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

        // Primary visitor
        $primaryQrUrl = route('visitor.scan', [
            'exhibition' => $exhibition->slug,
            'registrationCode' => $registrationCode,
        ]);
        $this->passImageDataUri = 'data:image/jpeg;base64,'.base64_encode(
            $qrService->generateVisitorPassImage($primaryQrUrl, $visitor->name, $exhibition, $visitor->passLabel())
        );

        // Additional persons
        $this->additionalPersonPasses = [];

        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personQrUrl = route('visitor.scan', [
                    'exhibition' => $exhibition->slug,
                    'registrationCode' => $registrationCode,
                ]).'?person='.($index + 1);

                $this->additionalPersonPasses[] = [
                    'name' => $person['name'],
                    'passImageDataUri' => 'data:image/jpeg;base64,'.base64_encode(
                        $qrService->generateVisitorPassImage($personQrUrl, $person['name'], $exhibition, $visitor->passLabel())
                    ),
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
