<?php

namespace App\Console\Commands;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateExhibitorPasswords extends Command
{
    protected $signature = 'bookings:generate-exhibitor-passwords {--dry-run : Preview without creating accounts}';

    protected $description = 'Create exhibitor user accounts and generate login passwords for payment-completed bookings';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY RUN mode - no accounts will be created');
        }

        $this->info('Finding payment-completed bookings without exhibitor accounts...');

        $bookings = Booking::query()
            ->where('status', BookingStatus::PaymentCompleted)
            ->whereNull('exhibitor_user_id')
            ->with('exhibition')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found that need exhibitor accounts.');

            return self::SUCCESS;
        }

        $count = $bookings->count();
        $this->info("Found {$count} booking(s) without exhibitor accounts.");

        $this->newLine();
        $this->table(
            ['Booking Code', 'Contact Person', 'Email', 'Phone', 'Exhibition'],
            $bookings->map(fn ($b) => [
                $b->booking_code,
                $b->contact_person,
                $b->email,
                $b->phone_number,
                $b->exhibition?->title ?? '-',
            ])
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn("DRY RUN: Would create {$count} exhibitor account(s)");
            $this->info('Run without --dry-run to create accounts');

            return self::SUCCESS;
        }

        $this->newLine();
        if (! $this->confirm("Create exhibitor accounts for these {$count} booking(s)?", true)) {
            $this->warn('Operation cancelled.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line(str_repeat('-', 80));
        $this->line(sprintf('%-12s %-30s %-20s %s', 'Booking', 'Email', 'Phone', 'Password'));
        $this->line(str_repeat('-', 80));

        foreach ($bookings as $booking) {
            $plainPassword = 'SGCCI@'.strtoupper(Str::random(6));

            $user = User::firstOrCreate(
                ['email' => $booking->email],
                [
                    'name' => $booking->contact_person,
                    'role' => 'exhibitor',
                    'password' => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]
            );

            // If user already existed, update to exhibitor role and set new password
            if (! $user->wasRecentlyCreated) {
                $user->update([
                    'role' => 'exhibitor',
                    'password' => Hash::make($plainPassword),
                ]);
            }

            $booking->update([
                'exhibitor_user_id' => $user->id,
                'login_password' => $plainPassword,
            ]);

            $this->line(sprintf(
                '%-12s %-30s %-20s %s',
                $booking->booking_code,
                $booking->email,
                $booking->phone_number,
                $plainPassword
            ));
        }

        $this->line(str_repeat('-', 80));
        $this->newLine();
        $this->info("Successfully created {$count} exhibitor account(s).");
        $this->warn('Passwords are stored on each booking and visible in the admin panel.');

        return self::SUCCESS;
    }
}
