<?php

namespace App\Console\Commands;

use App\BookingStatus;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPartialPaymentReminders extends Command
{
    protected $signature = 'bookings:send-partial-payment-reminders
                            {--dry-run : Preview which bookings would be notified without sending}';

    protected $description = 'Send payment link reminders to bookings with partial payments outstanding';

    public function handle(): int
    {
        $deadline = Carbon::create(2026, 2, 28);
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN — no messages will be sent.');
            $this->newLine();
        }

        $bookings = Booking::query()
            ->with('exhibition')
            ->where('status', BookingStatus::PaymentPending)
            ->where('amount_paid', '>', 0)
            ->where('remaining_amount', '>', 0)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found with partial payments outstanding.');

            return Command::SUCCESS;
        }

        $this->info("Found {$bookings->count()} booking(s) with partial payments outstanding.");
        $this->newLine();

        $headers = ['Booking Code', 'Contact Person', 'Phone', 'Amount Paid', 'Remaining', 'Total'];
        $rows = $bookings->map(fn ($b) => [
            $b->booking_code,
            $b->contact_person,
            $b->phone_code.' '.$b->phone_number,
            '₹'.number_format((float) $b->amount_paid, 2),
            '₹'.number_format((float) $b->remaining_amount, 2),
            '₹'.number_format((float) $b->total_with_gst, 2),
        ])->toArray();

        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn("Would send booking_confirmationpayment with deadline: {$deadline->format('M d, Y')}");

            return Command::SUCCESS;
        }

        if (! config('services.whatsapp.enabled')) {
            $this->error('WhatsApp is disabled in config. Set WHATSAPP_ENABLED=true to send messages.');

            return Command::FAILURE;
        }

        if (! $this->confirm("Send reminders to all {$bookings->count()} booking(s)?")) {
            $this->info('Aborted.');

            return Command::SUCCESS;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($bookings as $booking) {
            try {
                SendWhatsAppCampaign::dispatch(
                    campaignName: 'booking_confirmationpayment',
                    phoneCode: $booking->phone_code,
                    phoneNumber: $booking->phone_number,
                    templateParams: [
                        (string) $booking->contact_person,                  // {{1}} Contact Person Name
                        (string) $booking->exhibition->title,               // {{2}} Exhibition Title
                        implode(', ', $booking->selected_stalls),           // {{3}} Allotted Stalls
                        (string) $booking->booking_code,                    // {{4}} Booking Code
                        (string) number_format($booking->total_area, 0),    // {{5}} Total Area
                        (string) number_format($booking->total_with_gst, 2), // {{6}} Total Amount with GST
                        $deadline->format('M d, Y'),                        // {{7}} Payment Due Date (first)
                        (string) $booking->payment_link,                    // {{8}} Payment Link URL
                        $deadline->format('M d, Y'),                        // {{9}} Payment Due Date (repeated)
                    ]
                );

                $sentCount++;
                $this->info("✓ Sent to {$booking->contact_person} ({$booking->booking_code})");
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("✗ Failed for {$booking->booking_code}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done — Sent: {$sentCount}".($failedCount > 0 ? ", Failed: {$failedCount}" : ''));

        return Command::SUCCESS;
    }
}
