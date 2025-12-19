<?php

namespace App\Livewire\Admin\Inquiries;

use App\Models\Booking;
use Flux\Flux;
use Livewire\Component;

class ExportBookings extends Component
{
    public bool $showModal = false;

    public array $selectedStatuses = [];

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $includeMembershipInfo = true;

    public bool $includePaymentInfo = true;

    public bool $includeContactInfo = true;

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

    public function openModal(): void
    {
        $this->showModal = true;
        $this->calculateResultCount();
    }

    public function calculateResultCount(): void
    {
        $query = Booking::query();

        if (! empty($this->selectedStatuses)) {
            $query->where(function ($q) {
                foreach ($this->selectedStatuses as $status) {
                    if ($status === 'part_payment_received') {
                        // Part payment: amount_paid > 0 AND remaining_amount > 0
                        $q->orWhere(function ($subQuery) {
                            $subQuery->where('amount_paid', '>', 0)
                                ->where('remaining_amount', '>', 0);
                        });
                    } else {
                        $q->orWhere('status', $status);
                    }
                }
            });
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
        $query = Booking::query()->with(['exhibition']);

        if (! empty($this->selectedStatuses)) {
            $query->where(function ($q) {
                foreach ($this->selectedStatuses as $status) {
                    if ($status === 'part_payment_received') {
                        // Part payment: amount_paid > 0 AND remaining_amount > 0
                        $q->orWhere(function ($subQuery) {
                            $subQuery->where('amount_paid', '>', 0)
                                ->where('remaining_amount', '>', 0);
                        });
                    } else {
                        $q->orWhere('status', $status);
                    }
                }
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $bookings = $query->latest()->get();

        if ($bookings->isEmpty()) {
            Flux::toast(
                heading: 'No Data',
                variant: 'warning',
                text: 'No bookings found matching your criteria.'
            );

            return;
        }

        // Generate CSV
        $filename = 'bookings_export_'.now()->format('Y-m-d_His').'.csv';
        $handle = fopen('php://temp', 'r+');

        // Add BOM for proper UTF-8 encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Build header row
        $headers = [
            'Booking Code',
            'Brand Name',
            'Facia Name',
            'Trophy Name',
            'Company Profile',
            'Status',
            'Stalls',
            'Total Area (sqm)',
            'Space Type',
            'Total Price',
            'Discount %',
            'Discount Amount',
            'Price After Discount',
            'GST Amount',
            'Total With GST',
            'Booking Date',
        ];

        if ($this->includeContactInfo) {
            array_push($headers, 'Contact Person', 'Email', 'Phone', 'City', 'GST Number');
        }

        if ($this->includeMembershipInfo) {
            array_push($headers, 'SGCCI Member', 'Membership Type', 'Membership Number');
        }

        if ($this->includePaymentInfo) {
            array_push($headers, 'Amount Paid', 'Remaining Amount', 'Payment Status', 'Payment Date', 'Last Payment Date');
        }

        fputcsv($handle, $headers);

        // Add data rows
        foreach ($bookings as $booking) {
            // Determine the status label
            $statusLabel = $booking->status->label();
            if ($booking->hasPartialPayment()) {
                $statusLabel = 'Part Payment Received';
            }

            $row = [
                $booking->booking_code,
                $booking->brand_name,
                $booking->facia_name ?? '',
                $booking->trophy_name ?? '',
                $booking->company_profile ?? '',
                $statusLabel,
                implode(', ', $booking->selected_stalls),
                $booking->total_area,
                ucfirst($booking->space_type),
                $booking->total_price,
                $booking->discount_percentage,
                $booking->discount_amount,
                $booking->price_after_discount,
                $booking->gst_amount,
                $booking->total_with_gst,
                $booking->created_at->format('Y-m-d H:i:s'),
            ];

            if ($this->includeContactInfo) {
                array_push(
                    $row,
                    $booking->contact_person,
                    $booking->email,
                    $booking->phone_code.' '.$booking->phone_number,
                    $booking->city ?? '',
                    $booking->gst_number ?? ''
                );
            }

            if ($this->includeMembershipInfo) {
                array_push(
                    $row,
                    $booking->is_sgcci_member ? 'Yes' : 'No',
                    $booking->membership_type ? ucwords(str_replace('-', ' ', $booking->membership_type)) : '',
                    $booking->membership_number ?? ''
                );
            }

            if ($this->includePaymentInfo) {
                // Get last payment date from payment_history JSON array
                $lastPaymentDate = '';
                if (! empty($booking->payment_history) && is_array($booking->payment_history)) {
                    $paymentHistory = $booking->payment_history;
                    $lastPayment = end($paymentHistory);
                    $lastPaymentDate = $lastPayment['recorded_at'] ?? '';
                }

                array_push(
                    $row,
                    $booking->amount_paid,
                    $booking->remaining_amount,
                    $booking->isPaymentCompleted() ? 'Completed' : ($booking->amount_paid > 0 ? 'Partial' : 'Pending'),
                    $booking->payment_completed_at?->format('Y-m-d H:i:s') ?? '',
                    $lastPaymentDate
                );
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Flux::toast(
            heading: 'Export Ready!',
            variant: 'success',
            text: "Exported {$bookings->count()} bookings successfully."
        );

        $this->showModal = false;

        // Return the CSV file for download using Livewire's download response
        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.inquiries.export-bookings');
    }
}
