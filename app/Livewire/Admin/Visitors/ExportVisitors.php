<?php

namespace App\Livewire\Admin\Visitors;

use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Flux\Flux;
use Livewire\Component;

class ExportVisitors extends Component
{
    public bool $showModal = false;

    public array $selectedStatuses = [];

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $exhibitionId = null;

    public bool $includePersonalInfo = true;

    public bool $includeBusinessInfo = true;

    public bool $includePaymentInfo = true;

    public bool $includeAdditionalPersons = true;

    public int $resultCount = 0;

    public function mount(): void
    {
        // Default to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedSelectedStatuses(): void
    {
        $this->calculateResultCount();
    }

    public function updatedDateFrom(): void
    {
        $this->calculateResultCount();
    }

    public function updatedDateTo(): void
    {
        $this->calculateResultCount();
    }

    public function updatedExhibitionId(): void
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
        $query = ExhibitionVisitor::query();

        if (! empty($this->selectedStatuses)) {
            $query->whereIn('status', $this->selectedStatuses);
        }

        if ($this->exhibitionId) {
            $query->where('exhibition_id', $this->exhibitionId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $this->resultCount = $query->count();
    }

    public function export()
    {
        $query = ExhibitionVisitor::query()->with(['exhibition']);

        if (! empty($this->selectedStatuses)) {
            $query->whereIn('status', $this->selectedStatuses);
        }

        if ($this->exhibitionId) {
            $query->where('exhibition_id', $this->exhibitionId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $visitors = $query->latest()->get();

        if ($visitors->isEmpty()) {
            Flux::toast(
                heading: 'No Data',
                variant: 'warning',
                text: 'No visitors found matching your criteria.'
            );

            return;
        }

        // Generate CSV
        $filename = 'visitors_export_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // Add BOM for proper UTF-8 encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Build header row
        $headers = [
            'Registration Code',
            'Exhibition',
            'Status',
            'Registration Date',
        ];

        if ($this->includePersonalInfo) {
            array_push($headers, 'Name', 'Email', 'Phone Number', 'Designation');
        }

        if ($this->includeBusinessInfo) {
            array_push($headers, 'Company Name', 'Business Segment', 'Sub Business Segment', 'City', 'State');
        }

        if ($this->includePaymentInfo) {
            array_push($headers, 'Payment Amount', 'Payment Status', 'Payment Method', 'Payment Date', 'Transaction ID');
        }

        if ($this->includeAdditionalPersons) {
            array_push($headers, 'Total Persons', 'Additional Persons');
        }

        fputcsv($handle, $headers);

        // Add data rows
        foreach ($visitors as $visitor) {
            $row = [
                $visitor->registration_code,
                $visitor->exhibition->title ?? '',
                $visitor->status->label(),
                $visitor->created_at->format('Y-m-d H:i:s'),
            ];

            if ($this->includePersonalInfo) {
                array_push(
                    $row,
                    $visitor->name,
                    $visitor->email ?? '',
                    $visitor->phone_number,
                    $visitor->designation ?? ''
                );
            }

            if ($this->includeBusinessInfo) {
                array_push(
                    $row,
                    $visitor->company_name ?? '',
                    $visitor->business_segment,
                    $visitor->sub_business_segment,
                    $visitor->city,
                    $visitor->state ?? ''
                );
            }

            if ($this->includePaymentInfo) {
                array_push(
                    $row,
                    $visitor->payment_amount ? number_format((float) $visitor->payment_amount, 2) : '',
                    $visitor->payment_status ?? '',
                    $visitor->payment_method ?? '',
                    $visitor->payment_completed_at?->format('Y-m-d H:i:s') ?? '',
                    $visitor->payment_transaction_id ?? ''
                );
            }

            if ($this->includeAdditionalPersons) {
                $totalPersons = 1 + count($visitor->additional_persons ?? []);
                $additionalNames = ! empty($visitor->additional_persons)
                    ? implode(', ', array_column($visitor->additional_persons, 'name'))
                    : '';

                array_push($row, $totalPersons, $additionalNames);
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Flux::toast(
            heading: 'Export Ready!',
            variant: 'success',
            text: "Exported {$visitors->count()} visitors successfully."
        );

        $this->showModal = false;

        // Return the CSV file for download using Livewire's download response
        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function render()
    {
        $exhibitions = \App\Models\Exhibition::orderBy('title')->get();
        $statuses = VisitorRegistrationStatus::cases();

        return view('livewire.admin.visitors.export-visitors', [
            'exhibitions' => $exhibitions,
            'statuses' => $statuses,
        ]);
    }
}
