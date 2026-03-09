<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Scans;

use App\Models\ExhibitionVisitor;
use Flux\Flux;
use Livewire\Component;

class ExportScans extends Component
{
    public bool $showModal = false;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $entryFilter = '';

    public bool $includeExitTime = true;

    public int $resultCount = 0;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedDateFrom(): void
    {
        $this->calculateResultCount();
    }

    public function updatedDateTo(): void
    {
        $this->calculateResultCount();
    }

    public function updatedEntryFilter(): void
    {
        $this->calculateResultCount();
    }

    public function openModal(): void
    {
        $this->showModal = true;
        $this->calculateResultCount();
    }

    public function calculateResultCount(): void
    {
        $this->resultCount = $this->buildQuery()->count();
    }

    private function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return ExhibitionVisitor::query()
            ->whereNotNull('entered_at')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('entered_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('entered_at', '<=', $this->dateTo))
            ->when($this->entryFilter === 'inside', fn ($q) => $q->whereNull('exited_at'))
            ->when($this->entryFilter === 'exited', fn ($q) => $q->whereNotNull('exited_at'));
    }

    public function export(): mixed
    {
        $visitors = $this->buildQuery()->with('exhibition')->orderBy('entered_at')->get();

        if ($visitors->isEmpty()) {
            Flux::toast(heading: 'No Data', variant: 'warning', text: 'No scan records match your criteria.');

            return null;
        }

        $filename = 'scans_export_'.now()->format('Y-m-d_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        $headers = ['Registration Code', 'Name', 'Phone', 'Company', 'City', 'State', 'Entry Time'];

        if ($this->includeExitTime) {
            $headers[] = 'Exit Time';
            $headers[] = 'Currently Inside';
        }

        fputcsv($handle, $headers);

        foreach ($visitors as $visitor) {
            $row = [
                $visitor->registration_code,
                $visitor->name,
                $visitor->phone_number,
                $visitor->company_name ?? '',
                $visitor->city ?? '',
                $visitor->state ?? '',
                $visitor->entered_at->format('Y-m-d H:i:s'),
            ];

            if ($this->includeExitTime) {
                $row[] = $visitor->exited_at?->format('Y-m-d H:i:s') ?? '';
                $row[] = $visitor->exited_at ? 'No' : 'Yes';
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Flux::toast(heading: 'Export Ready!', variant: 'success', text: "Exported {$visitors->count()} scan records.");

        $this->showModal = false;

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.scans.export-scans');
    }
}
