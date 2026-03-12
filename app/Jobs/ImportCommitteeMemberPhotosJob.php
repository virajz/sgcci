<?php

namespace App\Jobs;

use App\Models\CommitteeMember;
use App\Models\MemberImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportCommitteeMemberPhotosJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public MemberImport $import) {}

    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);

        $zipPath = Storage::disk('local')->path($this->import->file_path);

        if (! file_exists($zipPath)) {
            $this->import->update([
                'status' => 'failed',
                'error_message' => 'ZIP file not found.',
            ]);

            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            $this->import->update([
                'status' => 'failed',
                'error_message' => 'Could not open ZIP file.',
            ]);

            return;
        }

        $matched = 0;
        $skipped = 0;
        $processed = 0;
        $total = $zip->numFiles;

        for ($i = 0; $i < $total; $i++) {
            $filename = $zip->getNameIndex($i);

            // Skip directories and non-jpg files
            if (str_ends_with($filename, '/') || ! preg_match('/\.jpe?g$/i', $filename)) {
                continue;
            }

            $processed++;
            $basename = basename($filename);
            $membershipNumber = pathinfo($basename, PATHINFO_FILENAME);

            $member = CommitteeMember::where('membership_number', $membershipNumber)->first();

            if (! $member) {
                $skipped++;

                continue;
            }

            // Delete old photo if exists
            if ($member->photo && Storage::exists($member->photo)) {
                Storage::delete($member->photo);
            }

            $imageData = $zip->getFromIndex($i);
            $storagePath = 'committee-members/photos/'.$membershipNumber.'.jpg';

            Storage::put($storagePath, $imageData);

            $member->update(['photo' => $storagePath]);

            $matched++;

            // Update progress every 10 photos
            if ($processed % 10 === 0) {
                $this->import->update([
                    'processed_rows' => $processed,
                    'imported_rows' => $matched,
                    'skipped_rows' => $skipped,
                ]);
            }
        }

        $zip->close();

        Storage::disk('local')->delete($this->import->file_path);

        $this->import->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'imported_rows' => $matched,
            'skipped_rows' => $skipped,
        ]);
    }
}
