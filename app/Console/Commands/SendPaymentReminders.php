<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Booking;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:send-payment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders to customers with upcoming payment deadlines (3 days before)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for bookings with upcoming payment deadlines...');

        // Get bookings with payment deadline approaching (within 3 days)
        $bookings = Booking::paymentDeadlineApproaching()
            ->where(function ($query) {
                // Only send if no reminder sent yet, or last reminder was sent more than 1 day ago
                $query->whereNull('last_payment_reminder_sent_at')
                    ->orWhere('last_payment_reminder_sent_at', '<', now()->subDay());
            })
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found with upcoming payment deadlines.');

            return Command::SUCCESS;
        }

        $this->info("Found {$bookings->count()} booking(s) requiring payment reminders.");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($bookings as $booking) {
            $daysUntilDeadline = now()->diffInDays($booking->partial_payment_deadline, false);

            $this->line("Processing booking: {$booking->booking_code} (Deadline in {$daysUntilDeadline} days)");

            try {
                if (config('services.whatsapp.enabled')) {
                    SendWhatsAppCampaign::dispatch(
                        campaignName: 'payment_reminder',
                        phoneCode: $booking->phone_code,
                        phoneNumber: $booking->phone_number,
                        templateParams: [
                            $booking->contact_person,
                            $booking->booking_code,
                            number_format($booking->remaining_amount, 2),
                            $booking->partial_payment_deadline->format('M d, Y'),
                            (string) $daysUntilDeadline,
                            implode(', ', $booking->selected_stalls),
                        ]
                    );

                    // Update the reminder timestamp
                    $booking->update([
                        'last_payment_reminder_sent_at' => now(),
                    ]);

                    $sentCount++;
                    $this->info("✓ Reminder sent to {$booking->contact_person}");
                } else {
                    $this->warn("WhatsApp is disabled. Skipping {$booking->booking_code}");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Failed to send reminder for {$booking->booking_code}: {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info('Payment reminders processed:');
        $this->info("  Sent: {$sentCount}");
        if ($failedCount > 0) {
            $this->warn("  Failed: {$failedCount}");
        }

        return Command::SUCCESS;
    }
}
