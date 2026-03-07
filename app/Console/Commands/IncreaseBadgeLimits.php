<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class IncreaseBadgeLimits extends Command
{
    protected $signature = 'bookings:increase-badge-limits {--dry-run : Preview changes without applying}';

    protected $description = 'Increase exhibitor badge limits to 10 for all bookings currently at 5 or below';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made');
        }

        $bookings = Booking::query()
            ->where('badge_limit', '<=', 5)
            ->with('exhibition')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings with a badge limit of 5 or below found.');

            return self::SUCCESS;
        }

        $count = $bookings->count();
        $this->info("Found {$count} booking(s) with a badge limit of 5 or below.");

        $this->newLine();
        $this->table(
            ['Booking Code', 'Brand Name', 'Exhibition', 'Current Limit'],
            $bookings->map(fn ($b) => [
                $b->booking_code,
                $b->brand_name,
                $b->exhibition?->title ?? '-',
                $b->badge_limit,
            ])
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn("DRY RUN: {$count} booking(s) would be updated to a badge limit of 10.");

            return self::SUCCESS;
        }

        $this->newLine();

        if (! $this->confirm("Update badge limit to 10 for all {$count} booking(s)?", true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Booking::query()
            ->where('badge_limit', '<=', 5)
            ->update(['badge_limit' => 10]);

        $this->info("Done. {$count} booking(s) updated to a badge limit of 10.");

        return self::SUCCESS;
    }
}
