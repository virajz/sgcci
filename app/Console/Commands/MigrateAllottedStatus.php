<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class MigrateAllottedStatus extends Command
{
    protected $signature = 'bookings:migrate-allotted-status {--dry-run : Preview changes without applying}';

    protected $description = 'Migrate bookings with legacy "allotted" status to "payment_completed"';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Finding bookings with legacy "allotted" status...');

        $bookings = Booking::query()
            ->where('status', 'allotted')
            ->with('exhibition')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings with "allotted" status found.');

            return self::SUCCESS;
        }

        $count = $bookings->count();
        $this->info("Found {$count} booking(s) with \"allotted\" status.");

        $this->newLine();
        $this->table(
            ['Booking Code', 'Contact Person', 'Email', 'Exhibition', 'Paid At'],
            $bookings->map(fn ($b) => [
                $b->booking_code,
                $b->contact_person,
                $b->email,
                $b->exhibition?->title ?? '-',
                $b->payment_completed_at?->format('Y-m-d H:i') ?? '-',
            ])
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn("DRY RUN: Would update {$count} booking(s) to \"payment_completed\"");
            $this->info('Run without --dry-run to apply changes');

            return self::SUCCESS;
        }

        $this->newLine();
        if (! $this->confirm("Update these {$count} booking(s) to \"payment_completed\"?", true)) {
            $this->warn('Operation cancelled.');

            return self::FAILURE;
        }

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'payment_completed']);
            $this->line("✓ Updated booking #{$booking->booking_code} ({$booking->email})");
        }

        $this->newLine();
        $this->info("Successfully migrated {$count} booking(s) to \"payment_completed\".");

        return self::SUCCESS;
    }
}
