<?php

namespace App\Livewire\Admin\Visitors;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public ExhibitionVisitor $visitor;

    public bool $showDeleteModal = false;

    public bool $showMarkPaidModal = false;

    public function mount(ExhibitionVisitor $visitor): void
    {
        $this->visitor = $visitor->load('exhibition');
    }

    public function confirmMarkPaid(): void
    {
        $this->showMarkPaidModal = true;
    }

    public function markPaymentCompleted(): void
    {
        if (! Auth::user()->isAdmin()) {
            Flux::toast(
                heading: 'Unauthorized',
                variant: 'danger',
                text: 'Only admins can manually mark payment as completed.'
            );
            $this->showMarkPaidModal = false;

            return;
        }

        if (! in_array($this->visitor->status, [
            VisitorRegistrationStatus::PaymentPending,
            VisitorRegistrationStatus::PaymentFailed,
        ])) {
            Flux::toast(
                heading: 'Invalid Status',
                variant: 'danger',
                text: 'Only registrations with payment pending or failed status can be manually confirmed.'
            );
            $this->showMarkPaidModal = false;

            return;
        }

        $this->visitor->update([
            'status' => VisitorRegistrationStatus::Confirmed,
            'payment_method' => $this->visitor->payment_method ?? 'Manual',
            'payment_status' => 'Success',
            'payment_completed_at' => now(),
        ]);

        $this->visitor->refresh();

        if (config('services.whatsapp.enabled')) {
            $exhibition = $this->visitor->exhibition;
            $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');
            $amountPaid = '₹'.number_format((float) $this->visitor->payment_amount, 2);
            $primaryFirstName = explode(' ', trim($this->visitor->name))[0];
            $primaryImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$this->visitor->registration_code.'/image';

            SendWhatsAppCampaign::dispatch(
                campaignName: 'Paidregistration1',
                phoneCode: '',
                phoneNumber: $this->visitor->phone_number,
                templateParams: [
                    $primaryFirstName,
                    $exhibition->title,
                    $this->visitor->registration_code,
                    $primaryFirstName,
                    $this->visitor->company_name ?: 'N/A',
                    $this->visitor->city,
                    $amountPaid,
                    $this->visitor->payment_transaction_id ?: 'N/A',
                    now()->format('d-m-Y'),
                    $exhibitionDates,
                ],
                paramsFallbackValue: ['FirstName' => 'Guest'],
                media: [
                    'url' => $primaryImageUrl,
                    'filename' => 'visitor_pass_'.$this->visitor->registration_code,
                ]
            );

            foreach ($this->visitor->additional_persons ?? [] as $index => $person) {
                $personFirstName = explode(' ', trim($person['name']))[0];
                $personImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$this->visitor->registration_code.'/image?personIndex='.$index;

                SendWhatsAppCampaign::dispatch(
                    campaignName: 'Paidregistration1',
                    phoneCode: '',
                    phoneNumber: $person['phone_number'] ?? $this->visitor->phone_number,
                    templateParams: [
                        $personFirstName,
                        $exhibition->title,
                        $this->visitor->registration_code,
                        $personFirstName,
                        $this->visitor->company_name ?: 'N/A',
                        $this->visitor->city,
                        $amountPaid,
                        $this->visitor->payment_transaction_id ?: 'N/A',
                        now()->format('d-m-Y'),
                        $exhibitionDates,
                    ],
                    paramsFallbackValue: ['FirstName' => 'Guest'],
                    media: [
                        'url' => $personImageUrl,
                        'filename' => 'visitor_pass_'.$this->visitor->registration_code.'_person_'.($index + 1),
                    ]
                );
            }
        }

        $this->showMarkPaidModal = false;

        Flux::toast(
            heading: 'Payment Confirmed!',
            variant: 'success',
            text: 'Registration has been manually marked as confirmed and WhatsApp passes sent.'
        );
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteVisitor(): void
    {
        $this->visitor->delete();

        Flux::toast(
            heading: 'Visitor Deleted!',
            variant: 'success',
            text: 'Visitor registration has been removed successfully.'
        );

        $this->redirect(route('admin.visitors.index'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.visitors.show');
    }
}
