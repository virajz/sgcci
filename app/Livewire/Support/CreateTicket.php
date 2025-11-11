<?php

namespace App\Livewire\Support;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.front')]
class CreateTicket extends Component
{
    use WithFileUploads;

    #[Validate('required|string|exists:bookings,booking_code')]
    public string $bookingCode = '';

    public ?Booking $booking = null;

    public bool $bookingVerified = false;

    public array $uploadedDocuments = [];

    public function verifyBooking(): void
    {
        $this->validate([
            'bookingCode' => 'required|string|exists:bookings,booking_code',
        ]);

        $booking = Booking::where('booking_code', $this->bookingCode)->first();

        if (! $booking) {
            $this->addError('bookingCode', 'Booking not found.');

            return;
        }

        // Check if booking is rejected, released, or cancelled
        if (! in_array($booking->status, [BookingStatus::Rejected, BookingStatus::Refunded, BookingStatus::Cancelled])) {
            $this->addError('bookingCode', 'Support tickets can only be created for rejected, refunded, or cancelled bookings.');

            return;
        }

        $this->booking = $booking;
        $this->bookingVerified = true;
    }

    public function handleFileUpload(array $data): void
    {
        $path = $data['path'] ?? null;
        $filename = $data['filename'] ?? null;

        if ($path && $filename) {
            $this->uploadedDocuments[] = [
                'path' => $path,
                'filename' => $filename,
            ];
        }
    }

    public function removeDocument(int $index): void
    {
        if (isset($this->uploadedDocuments[$index])) {
            $document = $this->uploadedDocuments[$index];
            $path = is_array($document) ? $document['path'] : $document;

            // Delete the file from storage
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            array_splice($this->uploadedDocuments, $index, 1);
        }
    }

    public function submit(): void
    {
        if (! $this->bookingVerified || ! $this->booking) {
            $this->addError('bookingCode', 'Please verify your booking first.');

            return;
        }

        if (empty($this->uploadedDocuments)) {
            $this->addError('documents', 'Please upload at least one document.');

            return;
        }

        $ticket = SupportTicket::create([
            'booking_id' => $this->booking->id,
            'documents' => $this->uploadedDocuments,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Support ticket created successfully! Ticket Number: '.$ticket->ticket_number);

        $this->redirect(route('home'));
    }

    public function render()
    {
        return view('livewire.support.create-ticket');
    }
}
