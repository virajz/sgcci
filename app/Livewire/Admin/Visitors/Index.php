<?php

namespace App\Livewire\Admin\Visitors;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\ExhibitionVisitor;
use App\Services\CurrentExhibition;
use App\VisitorRegistrationStatus;
use Flux\DateRange;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $invitedFilter = '';

    public ?DateRange $dateRange = null;

    public string $entryDate = '';

    public bool $showDeleteModal = false;

    public ?int $visitorToDelete = null;

    public bool $showPersonsModal = false;

    public string $selectedVisitorName = '';

    /** @var array<int, array{name: string}> */
    public array $selectedPersons = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingInvitedFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateRange(): void
    {
        $this->resetPage();
    }

    public function updatingEntryDate(): void
    {
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->invitedFilter = '';
        $this->dateRange = null;
        $this->entryDate = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->invitedFilter !== ''
            || $this->dateRange !== null
            || $this->entryDate !== '';
    }

    public function confirmDelete(int $visitorId): void
    {
        $this->visitorToDelete = $visitorId;
        $this->showDeleteModal = true;
    }

    public function viewPersons(int $visitorId): void
    {
        $visitor = ExhibitionVisitor::findOrFail($visitorId);

        $this->selectedVisitorName = $visitor->name;
        $this->selectedPersons = $visitor->additional_persons ?? [];
        $this->showPersonsModal = true;
    }

    public function deleteVisitor(): void
    {
        if (! $this->visitorToDelete) {
            return;
        }

        $visitor = ExhibitionVisitor::findOrFail($this->visitorToDelete);
        $visitor->delete();

        Flux::toast(
            heading: 'Visitor Deleted!',
            variant: 'success',
            text: 'Visitor registration has been removed successfully.'
        );

        $this->showDeleteModal = false;
        $this->visitorToDelete = null;
    }

    public function sendWhatsApp(int $visitorId): void
    {
        $visitor = ExhibitionVisitor::with('exhibition')->findOrFail($visitorId);
        $exhibition = $visitor->exhibition;

        $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');

        // Determine payment info based on status
        if ($visitor->status === VisitorRegistrationStatus::Confirmed && $visitor->payment_amount) {
            $amountPaid = '₹'.number_format((float) $visitor->payment_amount, 2);
            $transactionId = $visitor->payment_transaction_id ?: 'N/A';
            $paymentDate = $visitor->payment_completed_at ? $visitor->payment_completed_at->format('d-m-Y') : 'N/A';
        } else {
            $amountPaid = 'Free Entry';
            $transactionId = 'N/A';
            $paymentDate = $visitor->created_at->format('d-m-Y');
        }

        // Send WhatsApp for primary visitor
        $primaryFirstName = explode(' ', trim($visitor->name))[0];
        $primaryImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $primaryFirstName,
                $exhibition->title,
                $visitor->registration_code,
                $primaryFirstName,
                $visitor->company_name ?: 'N/A',
                $visitor->city,
                $amountPaid,
                $transactionId,
                $paymentDate,
                $exhibitionDates,
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $primaryImageUrl,
                'filename' => 'visitor_pass_'.$visitor->registration_code,
            ]
        );

        // Send WhatsApp for each additional person
        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personFirstName = explode(' ', trim($person['name']))[0];
                $personImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image?personIndex='.$index;
                $personPhone = ! empty($person['phone_number']) ? $person['phone_number'] : $visitor->phone_number;

                SendWhatsAppCampaign::dispatch(
                    campaignName: 'Paidregistration1',
                    phoneCode: '',
                    phoneNumber: $personPhone,
                    templateParams: [
                        $personFirstName,
                        $exhibition->title,
                        $visitor->registration_code,
                        $personFirstName,
                        $visitor->company_name ?: 'N/A',
                        $visitor->city,
                        $amountPaid,
                        $transactionId,
                        $paymentDate,
                        $exhibitionDates,
                    ],
                    paramsFallbackValue: [
                        'FirstName' => 'Guest',
                    ],
                    media: [
                        'url' => $personImageUrl,
                        'filename' => 'visitor_pass_'.$visitor->registration_code.'_person_'.($index + 1),
                    ]
                );
            }
        }

        $totalMessages = 1 + count($visitor->additional_persons ?? []);

        Flux::toast(
            heading: 'WhatsApp Queued!',
            variant: 'success',
            text: "{$totalMessages} WhatsApp message(s) queued for {$visitor->name}"
        );
    }

    public function render()
    {
        $visitors = ExhibitionVisitor::query()
            ->with(['exhibition', 'invitedByBooking'])
            ->when(CurrentExhibition::id(), fn ($query, $id) => $query->where('exhibition_id', $id))
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('REPLACE(phone_number, \' \', \'\') LIKE ?', ['%'.str_replace(' ', '', $search).'%'])
                        ->orWhereRaw('LOWER(registration_code) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->invitedFilter === 'invited', function ($query) {
                $query->whereNotNull('invited_by_booking_id');
            })
            ->when($this->invitedFilter === 'with_pass', function ($query) {
                $query->where('with_invitation_pass', true);
            })
            ->when($this->invitedFilter === 'without_pass', function ($query) {
                $query->where('with_invitation_pass', false);
            })
            ->when($this->dateRange?->start(), function ($query) {
                $query->whereDate('created_at', '>=', $this->dateRange->start());
            })
            ->when($this->dateRange?->end(), function ($query) {
                $query->whereDate('created_at', '<=', $this->dateRange->end());
            })
            ->when($this->entryDate, function ($query) {
                $query->whereDate('entered_at', $this->entryDate);
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.visitors.index', [
            'visitors' => $visitors,
            'statuses' => VisitorRegistrationStatus::cases(),
            'hasActiveFilters' => $this->hasActiveFilters(),
        ]);
    }
}
