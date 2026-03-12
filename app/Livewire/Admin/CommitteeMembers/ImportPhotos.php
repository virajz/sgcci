<?php

namespace App\Livewire\Admin\CommitteeMembers;

use App\Models\CommitteeMember;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class ImportPhotos extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:zip|max:102400')]
    public $zipFile = null;

    public ?string $storedPath = null;

    public bool $processed = false;

    public int $matched = 0;

    public int $unmatched = 0;

    /** @var array<int, string> */
    public array $unmatchedFiles = [];

    public function updatedZipFile(): void
    {
        $this->validate(['zipFile' => 'required|file|mimes:zip|max:102400']);

        $this->storedPath = $this->zipFile->store('imports/committee-photos', 'local');
    }

    public function startImport(): void
    {
        if (! $this->storedPath) {
            return;
        }

        $zipPath = Storage::disk('local')->path($this->storedPath);
        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            Flux::toast(heading: 'Error', variant: 'danger', text: 'Could not open ZIP file.');

            return;
        }

        $matched = 0;
        $unmatched = 0;
        $unmatchedFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Skip directories and non-jpg files
            if (str_ends_with($filename, '/') || ! preg_match('/\.jpe?g$/i', $filename)) {
                continue;
            }

            // Strip any directory prefix (support zips with a subfolder)
            $basename = basename($filename);

            // Extract membership number from filename (strip extension)
            $membershipNumber = pathinfo($basename, PATHINFO_FILENAME);

            $member = CommitteeMember::where('membership_number', $membershipNumber)->first();

            if (! $member) {
                $unmatched++;
                $unmatchedFiles[] = $basename;

                continue;
            }

            // Delete old photo if exists
            if ($member->photo && Storage::exists($member->photo)) {
                Storage::delete($member->photo);
            }

            // Extract and store the image
            $imageData = $zip->getFromIndex($i);
            $storagePath = 'committee-members/photos/'.$membershipNumber.'.jpg';

            Storage::put($storagePath, $imageData);

            $member->update(['photo' => $storagePath]);

            $matched++;
        }

        $zip->close();

        Storage::disk('local')->delete($this->storedPath);

        $this->matched = $matched;
        $this->unmatched = $unmatched;
        $this->unmatchedFiles = array_slice($unmatchedFiles, 0, 20); // show max 20
        $this->processed = true;
        $this->storedPath = null;
        $this->zipFile = null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.committee-members.import-photos');
    }
}
