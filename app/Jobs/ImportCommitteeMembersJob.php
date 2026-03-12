<?php

namespace App\Jobs;

use App\Models\CommitteeMember;
use App\Models\MemberImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ImportCommitteeMembersJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public MemberImport $import) {}

    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);

        $path = Storage::disk('local')->path($this->import->file_path);

        if (! file_exists($path)) {
            $this->import->update([
                'status' => 'failed',
                'error_message' => 'Import file not found.',
            ]);

            return;
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->import->update([
                'status' => 'failed',
                'error_message' => 'Could not open import file.',
            ]);

            return;
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);
            $this->import->update([
                'status' => 'failed',
                'error_message' => 'CSV file appears to be empty.',
            ]);

            return;
        }

        $headers = array_map('trim', $headers);

        $imported = 0;
        $skipped = 0;
        $processed = 0;
        $chunkSize = 200;
        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_map(fn (string $v): string => $this->sanitize($v), $row);
            $chunk[] = array_combine($headers, array_pad($row, count($headers), ''));

            if (count($chunk) >= $chunkSize) {
                [$chunkImported, $chunkSkipped] = $this->processChunk($chunk);
                $imported += $chunkImported;
                $skipped += $chunkSkipped;
                $processed += count($chunk);
                $chunk = [];

                $this->import->update([
                    'processed_rows' => $processed,
                    'imported_rows' => $imported,
                    'skipped_rows' => $skipped,
                ]);
            }
        }

        if (! empty($chunk)) {
            [$chunkImported, $chunkSkipped] = $this->processChunk($chunk);
            $imported += $chunkImported;
            $skipped += $chunkSkipped;
            $processed += count($chunk);
        }

        fclose($handle);

        Storage::disk('local')->delete($this->import->file_path);

        $this->import->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
        ]);
    }

    /** @param array<int, array<string, string>> $chunk */
    private function processChunk(array $chunk): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($chunk as $data) {
            $name = trim($data['Name'] ?? '');

            if (empty($name)) {
                $skipped++;

                continue;
            }

            $membershipNumber = $data['Mem No'] ?: null;

            $attributes = [
                'sort_order' => (int) ($data['Sr No'] ?? 0),
                'membership_number' => $membershipNumber,
                'name' => $name,
                'post' => $data['Post'] ?: null,
                'post_for_badge' => $data['Post for Badges 1'] ?: null,
                'mobile' => $data['Mobile'] ?: null,
            ];

            if ($this->import->import_mode === 'overwrite' && $membershipNumber) {
                CommitteeMember::updateOrCreate(
                    ['membership_number' => $membershipNumber],
                    $attributes
                );
            } elseif ($this->import->import_mode === 'overwrite') {
                CommitteeMember::create($attributes);
            } else {
                if ($membershipNumber && CommitteeMember::where('membership_number', $membershipNumber)->exists()) {
                    $skipped++;

                    continue;
                }
                CommitteeMember::create($attributes);
            }

            $imported++;
        }

        return [$imported, $skipped];
    }

    private function sanitize(string $value): string
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\xc2\xa0/u', '', $value) ?? $value;

        return trim($value);
    }
}
