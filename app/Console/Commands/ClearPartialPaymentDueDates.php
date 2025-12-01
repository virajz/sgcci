<?php

namespace App\Console\Commands;

use App\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

class ClearPartialPaymentDueDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:clear-partial-payment-due-dates {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear payment_due_at for bookings with partial payments to prevent auto-release';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Finding bookings with partial payments that have payment_due_at set...');

        // Find bookings with:
        // 1. Any payment made (amount_paid > 0)
        // 2. Still have payment_due_at set
        // 3. Not fully paid yet (remaining_amount > 0)
        $bookings = Booking::query()
            ->where('amount_paid', '>', 0)
            ->whereNotNull('payment_due_at')
            ->where('remaining_amount', '>', 0)
            ->whereIn('status', [BookingStatus::PaymentPending, BookingStatus::Allotted])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found with partial payments and payment_due_at set.');

            return self::SUCCESS;
        }

        $count = $bookings->count();
        $this->info("Found {$count} booking(s) with partial payments.");

        $this->newLine();
        $this->table(
            ['Booking Code', 'Amount Paid', 'Remaining', 'Payment Due At', 'Status'],
            $bookings->map(fn ($b) => [
                $b->booking_code,
                '₹'.number_format($b->amount_paid, 2),
                '₹'.number_format($b->remaining_amount, 2),
                $b->payment_due_at?->format('Y-m-d H:i:s') ?? 'NULL',
                $b->status->value,
            ])
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn("DRY RUN: Would clear payment_due_at for {$count} booking(s)");
            $this->info('Run without --dry-run to apply changes');

            return self::SUCCESS;
        }

        $this->newLine();
        if (! $this->confirm("Clear payment_due_at for these {$count} booking(s)?", true)) {
            $this->warn('Operation cancelled.');

            return self::FAILURE;
        }

        foreach ($bookings as $booking) {
            $booking->update([
                'payment_due_at' => null,
            ]);

            $this->line("✓ Cleared payment_due_at for booking #{$booking->booking_code}");
        }

        $this->newLine();
        $this->info("Successfully cleared payment_due_at for {$count} booking(s).");
        $this->info('These bookings will no longer be auto-released by the expired bookings command.');

        return self::SUCCESS;
    }
}
