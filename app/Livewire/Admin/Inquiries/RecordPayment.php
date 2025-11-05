<?php

namespace App\Livewire\Admin\Inquiries;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RecordPayment extends Component
{
    public Booking $booking;

    public bool $showModal = false;

    public string $paymentType = 'partial'; // 'partial' or 'full'

    public ?float $paymentAmount = null;

    public string $paymentMethod = '';

    public string $transactionId = '';

    public string $notes = '';

    protected function rules(): array
    {
        $rules = [
            'paymentType' => ['required', 'in:partial,full'],
            'paymentMethod' => ['required', 'string', 'in:cash,cheque,bank_transfer,upi,card,other'],
            'transactionId' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->paymentType === 'partial') {
            $rules['paymentAmount'] = ['required', 'numeric', 'min:0.01', 'max:' . $this->booking->remaining_amount];
        }

        return $rules;
    }

    public function mount(Booking $booking): void
    {
        // Ensure user has admin privileges
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $this->booking = $booking;
        $this->calculateDefaultPayment();
    }

    public function updatedPaymentType(): void
    {
        $this->calculateDefaultPayment();
    }

    protected function calculateDefaultPayment(): void
    {
        if ($this->paymentType === 'partial') {
            // Default to 50% of total
            $this->paymentAmount = round($this->booking->total_with_gst * 0.5, 2);

            // If 50% already paid, suggest remaining amount
            if ($this->booking->amount_paid >= ($this->booking->total_with_gst * 0.5)) {
                $this->paymentAmount = $this->booking->remaining_amount;
            }
        } else {
            $this->paymentAmount = $this->booking->remaining_amount;
        }
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->paymentType = 'partial';
        $this->paymentMethod = '';
        $this->transactionId = '';
        $this->notes = '';
        $this->paymentAmount = null;
        $this->calculateDefaultPayment();
        $this->resetValidation();
    }

    public function recordPayment(): void
    {
        $validated = $this->validate();

        $amount = $this->paymentType === 'full'
            ? $this->booking->remaining_amount
            : $this->paymentAmount;

        $additionalData = [];
        if ($this->notes) {
            $additionalData['notes'] = $this->notes;
        }

        // Record the payment
        $this->booking->recordPayment(
            amount: $amount,
            method: $this->paymentMethod,
            transactionId: $this->transactionId ?: null,
            additionalData: $additionalData
        );

        // If this is first payment (50%), set deadline for remaining payment
        if ($this->booking->hasPartialPayment() && ! $this->booking->partial_payment_deadline) {
            $this->booking->update([
                'partial_payment_deadline' => now()->parse('2026-02-15'), // February 15, 2026
            ]);
        }

        // Refresh the booking to get updated values
        $this->booking->refresh();

        // Send appropriate WhatsApp notification
        if (config('services.whatsapp.enabled')) {
            if ($this->booking->isPaymentCompleted()) {
                // Send full payment success notification
                SendWhatsAppCampaign::dispatch(
                    campaignName: 'payment_success',
                    phoneCode: $this->booking->phone_code,
                    phoneNumber: $this->booking->phone_number,
                    templateParams: [
                        $this->booking->contact_person,
                        $this->booking->exhibition->title,
                        $this->booking->booking_code,
                        number_format($this->booking->total_with_gst, 2),
                        now()->format('M d, Y'),
                        implode(', ', $this->booking->selected_stalls),
                    ]
                );
            } else {
                // Send partial payment confirmation (we'll create this template)
                $paymentPercentage = round($this->booking->getPaymentPercentage(), 0);

                SendWhatsAppCampaign::dispatch(
                    campaignName: 'partial_payment_received',
                    phoneCode: $this->booking->phone_code,
                    phoneNumber: $this->booking->phone_number,
                    templateParams: [
                        $this->booking->contact_person,
                        number_format($amount, 2),
                        $this->booking->booking_code,
                        number_format($this->booking->remaining_amount, 2),
                        $this->booking->partial_payment_deadline?->format('M d, Y') ?? 'Feb 15, 2026',
                    ]
                );
            }
        }

        $this->closeModal();

        Flux::toast(
            heading: 'Payment Recorded!',
            variant: 'success',
            text: $this->booking->isPaymentCompleted()
                ? 'Full payment recorded successfully. Stalls confirmed!'
                : 'Partial payment recorded. Remaining: ₹' . number_format($this->booking->remaining_amount, 2)
        );

        $this->dispatch('booking-updated');
    }

    public function render()
    {
        return view('livewire.admin.inquiries.record-payment');
    }
}
