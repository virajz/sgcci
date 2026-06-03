<?php

declare(strict_types=1);

namespace App\Console\Commands\Exhibitions;

use App\Models\Exhibition;
use Illuminate\Console\Command;

class CloseEndedRegistrations extends Command
{
    protected $signature = 'exhibitions:close-ended-registrations
                            {--dry-run : Preview what would be updated without making changes}';

    protected $description = 'Close visitor registration for exhibitions whose end date has passed';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $exhibitions = Exhibition::query()
            ->where('registration_closed', false)
            ->whereDate('end_date', '<=', now()->toDateString())
            ->get();

        if ($exhibitions->isEmpty()) {
            $this->info('No ended exhibitions with open registration found.');

            return self::SUCCESS;
        }

        foreach ($exhibitions as $exhibition) {
            $this->line("  Closing registration: {$exhibition->title} (ended {$exhibition->end_date->format('Y-m-d')})");

            if (! $isDryRun) {
                $exhibition->update(['registration_closed' => true]);
            }
        }

        $count = $exhibitions->count();
        $suffix = $isDryRun ? ' (dry run — not saved)' : '';
        $this->info("Closed registration for {$count} exhibition(s){$suffix}.");

        return self::SUCCESS;
    }
}
