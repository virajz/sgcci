<?php

namespace App\Livewire\Admin\CommitteeMembers;

use App\Jobs\ImportCommitteeMemberPhotosJob;
use App\Models\MemberImport;
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

    public ?int $importId = null;

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

        $import = MemberImport::create([
            'type' => 'committee_photos',
            'file_path' => $this->storedPath,
            'import_mode' => 'overwrite',
            'status' => 'pending',
            'total_rows' => 0,
        ]);

        ImportCommitteeMemberPhotosJob::dispatch($import);

        $this->importId = $import->id;
        $this->storedPath = null;
        $this->zipFile = null;
    }

    public function pollStatus(): void
    {
        // Re-render to refresh import status from DB
    }

    public function render(): \Illuminate\View\View
    {
        $activeImport = $this->importId
            ? MemberImport::find($this->importId)
            : null;

        return view('livewire.admin.committee-members.import-photos', [
            'activeImport' => $activeImport,
        ]);
    }
}
