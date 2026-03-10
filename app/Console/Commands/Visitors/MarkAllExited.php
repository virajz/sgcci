<?php

declare(strict_types=1);

namespace App\Console\Commands\Visitors;

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use Illuminate\Console\Command;

class MarkAllExited extends Command
{
    protected $signature = 'visitors:mark-all-exited
                            {--dry-run : Preview what would be updated without making changes}';

    protected $description = 'Mark all currently-inside visitors (and their additional persons) as exited';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $exhibition = Exhibition::latest()->first();

        if (! $exhibition) {
            $this->error('No exhibition found.');

            return self::FAILURE;
        }

        $this->info("Exhibition: {$exhibition->name}");

        $visitors = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where(function ($q): void {
                // Primary visitor still inside, OR has additional persons that may be inside
                $q->whereNotNull('entered_at')
                    ->whereNull('exited_at')
                    ->orWhereNotNull('additional_persons');
            })
            ->get();

        $primaryCount = 0;
        $personCount = 0;

        foreach ($visitors as $visitor) {
            $updates = [];

            // Primary visitor inside
            if ($visitor->entered_at && ! $visitor->exited_at) {
                $primaryCount++;
                $this->line("  Primary: {$visitor->name} ({$visitor->registration_code})");
                $updates['exited_at'] = now();
            }

            // Additional persons
            $persons = is_array($visitor->additional_persons) ? $visitor->additional_persons : [];
            $personsChanged = false;

            foreach ($persons as $i => $person) {
                $enteredAt = $person['entered_at'] ?? null;
                $exitedAt = $person['exited_at'] ?? null;

                if ($enteredAt && ! $exitedAt) {
                    $personCount++;
                    $this->line("  Person:  {$person['name']} ({$visitor->registration_code} #".($i + 1).')');
                    $persons[$i]['exited_at'] = now()->toIso8601String();
                    $personsChanged = true;
                }
            }

            if ($personsChanged) {
                $updates['additional_persons'] = $persons;
            }

            if (! $isDryRun && ! empty($updates)) {
                $visitor->update($updates);
            }
        }

        if ($primaryCount === 0 && $personCount === 0) {
            $this->info('No visitors currently inside — nothing to do.');

            return self::SUCCESS;
        }

        $suffix = $isDryRun ? ' (dry run — not saved)' : '';
        $this->info("Marked {$primaryCount} primary visitor(s) and {$personCount} additional person(s) as exited{$suffix}.");

        return self::SUCCESS;
    }
}
