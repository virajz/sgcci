<?php

namespace App\Console\Commands;

use App\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

class ReleaseExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release bookings that have not been paid within 3 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired bookings...');

        $expiredBookings = Booking::query()
            ->where('status', BookingStatus::PaymentPending)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired bookings found.');

            return self::SUCCESS;
        }

        $count = $expiredBookings->count();
        $this->info("Found {$count} expired booking(s).");

        foreach ($expiredBookings as $booking) {
            $booking->update([
                'status' => BookingStatus::Expired,
            ]);

            $this->line("Released booking #{$booking->booking_code} (stalls: ".implode(', ', $booking->selected_stalls).')');
        }

        $this->info("Successfully released {$count} expired booking(s).");

        return self::SUCCESS;
    }
}
