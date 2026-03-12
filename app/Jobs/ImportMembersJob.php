<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\MemberImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ImportMembersJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

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

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
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
        $chunkSize = 500;
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

        // Process remaining rows
        if (! empty($chunk)) {
            [$chunkImported, $chunkSkipped] = $this->processChunk($chunk);
            $imported += $chunkImported;
            $skipped += $chunkSkipped;
            $processed += count($chunk);
        }

        fclose($handle);

        // Clean up the temp file
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
            $membershipNumber = trim($data['Membership No'] ?? '');
            $contactName = trim($data['ContactName'] ?? '');

            if (empty($membershipNumber) || empty($contactName)) {
                $skipped++;

                continue;
            }

            $attributes = [
                'membership_number' => $membershipNumber,
                'contact_name' => $contactName,
                'company' => $data['Company'] ?: null,
                'address_type_a' => $data['Address Type (A)'] ?: null,
                'address1_a' => $data['Address 1 (A)'] ?: null,
                'address2_a' => $data['Address 2 (A)'] ?: null,
                'area_a' => $data['Area (A)'] ?: null,
                'city_a' => $data['City (A)'] ?: null,
                'state_a' => $data['State (A)'] ?: null,
                'pincode_a' => $data['Pincode (A)'] ?: null,
                'address_type_b' => $data['Address Type (B)'] ?: null,
                'address1_b' => $data['Address 1 (B)'] ?: null,
                'address2_b' => $data['Address 2 (B)'] ?: null,
                'area_b' => $data['Area (B)'] ?: null,
                'city_b' => $data['City (B)'] ?: null,
                'state_b' => $data['State (B)'] ?: null,
                'pincode_b' => $data['Pincode (B)'] ?: null,
                'office_phone' => $data['Office Phone'] ?: null,
                'home_phone' => $data['Home Phone'] ?: null,
                'cell_no' => $data['Cell No'] ?: null,
                'email' => $data['E-Mail Address'] ?: null,
                'web' => $data['Web'] ?: null,
                'post' => $data['Post'] ?: null,
                'type' => $data['Type'] ?: null,
                'dob' => $data['DOB'] ?: null,
                'aadhar_no' => $data['Aadhar No'] ?: null,
                'pan_no' => $data['Pan No'] ?: null,
                'gst_no' => $data['GST No'] ?: null,
                'nature_of_business' => $data['Nature Of Business'] ?: null,
                'business_segment' => $data['Business Segment'] ?: null,
                'turn_over' => $data['Turn Over'] ?: null,
                'scale_of_business' => $data['Scale Of Business'] ?: null,
                'spouse_name' => $data['Spouse Name'] ?: null,
                'spouse_phone_no' => $data['Spouse Phone No'] ?: null,
                'blood_group' => $data['Blood Group'] ?: null,
            ];

            if ($this->import->import_mode === 'overwrite') {
                Member::updateOrCreate(
                    ['membership_number' => $membershipNumber],
                    $attributes
                );
            } else {
                if (Member::where('membership_number', $membershipNumber)->exists()) {
                    $skipped++;

                    continue;
                }

                Member::create($attributes);
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
