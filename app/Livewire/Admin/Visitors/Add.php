<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Visitors;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Services\CurrentExhibition;
use App\VisitorRegistrationStatus;
use App\VisitorType;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Add extends Component
{
    /** @var array<int, array{name: string, phone_number: string, visitor_type: string}> */
    public array $rows = [];

    public int $addedCount = 0;

    public int $skippedCount = 0;

    public bool $done = false;

    public function mount(): void
    {
        $this->rows = $this->emptyRows(5);
    }

    public function addRows(): void
    {
        $this->rows = array_merge($this->rows, $this->emptyRows(5));
    }

    public function removeRow(int $index): void
    {
        array_splice($this->rows, $index, 1);
        $this->rows = array_values($this->rows);
    }

    public function save(): void
    {
        $filled = array_filter($this->rows, fn (array $r) => trim($r['name']) !== '' || trim($r['phone_number']) !== '');

        if (empty($filled)) {
            Flux::toast(text: 'Please fill in at least one row.', variant: 'warning');

            return;
        }

        $exhibition = CurrentExhibition::model() ?? Exhibition::latest()->firstOrFail();
        $validTypes = array_column(VisitorType::cases(), 'value');

        $this->addedCount = 0;
        $this->skippedCount = 0;

        foreach ($filled as $row) {
            $name = trim($row['name']);
            $phone = trim($row['phone_number']);
            $type = in_array($row['visitor_type'], $validTypes) ? $row['visitor_type'] : null;

            if ($name === '' || $phone === '') {
                $this->skippedCount++;

                continue;
            }

            // Skip duplicate phone in this exhibition
            $alreadyExists = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
                ->where('phone_number', $phone)
                ->where('status', VisitorRegistrationStatus::Confirmed)
                ->exists();

            if ($alreadyExists) {
                $this->skippedCount++;

                continue;
            }

            $visitor = ExhibitionVisitor::create([
                'exhibition_id' => $exhibition->id,
                'phone_number' => $phone,
                'name' => $name,
                'state' => 'Gujarat',
                'city' => 'Surat',
                'source' => 'admin_bulk',
                'visitor_type' => $type ?: null,
                'status' => VisitorRegistrationStatus::Confirmed,
            ]);

            if (config('services.whatsapp.enabled')) {
                $this->dispatchWhatsApp($visitor, $exhibition);
            }

            $this->addedCount++;
        }

        $this->done = true;
        $this->rows = $this->emptyRows(5);
    }

    public function reset_(): void
    {
        $this->done = false;
        $this->addedCount = 0;
        $this->skippedCount = 0;
        $this->rows = $this->emptyRows(5);
    }

    private function dispatchWhatsApp(ExhibitionVisitor $visitor, Exhibition $exhibition): void
    {
        $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');
        $firstName = explode(' ', trim($visitor->name))[0];
        $imageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $firstName,
                $exhibition->title,
                $visitor->registration_code,
                $firstName,
                $visitor->company_name ?: 'N/A',
                $visitor->city,
                'Free Entry',
                'N/A',
                $visitor->created_at->format('d-m-Y'),
                $exhibitionDates,
            ],
            paramsFallbackValue: ['FirstName' => 'Guest'],
            media: [
                'url' => $imageUrl,
                'filename' => 'visitor_pass_'.$visitor->registration_code,
            ]
        );
    }

    /**
     * @return array<int, array{name: string, phone_number: string, visitor_type: string}>
     */
    private function emptyRows(int $count): array
    {
        return array_fill(0, $count, ['name' => '', 'phone_number' => '', 'visitor_type' => '']);
    }

    public function render(): View
    {
        return view('livewire.admin.visitors.add');
    }
}
