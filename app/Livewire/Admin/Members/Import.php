<?php

namespace App\Livewire\Admin\Members;

use App\Jobs\ImportMembersJob;
use App\Models\MemberImport;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Import extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:csv,txt|max:102400')]
    public $csvFile = null;

    // Path on local disk once uploaded — persists across Livewire requests
    public ?string $storedPath = null;

    public bool $showPreview = false;

    /** @var array<int, array<string, string>> */
    public array $previewRows = [];

    public int $totalRows = 0;

    public string $importMode = 'skip';

    public ?int $importId = null;

    public function updatedCsvFile(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:102400']);

        // Store immediately to local disk — safe on S3 production environments
        $this->storedPath = $this->csvFile->store('imports/members', 'local');

        $this->loadPreview();
    }

    private function loadPreview(): void
    {
        if (! $this->storedPath) {
            return;
        }

        $handle = Storage::disk('local')->readStream($this->storedPath);

        if (! $handle) {
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

            return;
        }

        $headers = array_map('trim', $headers);
        $rows = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $count++;
            if ($count <= 5) {
                $full = array_combine($headers, array_pad($row, count($headers), ''));
                $rows[] = [
                    'Membership No' => $full['Membership No'] ?? '',
                    'ContactName' => $full['ContactName'] ?? '',
                    'Company' => $full['Company'] ?? '',
                    'Post' => $full['Post'] ?? '',
                    'Type' => $full['Type'] ?? '',
                    'Cell No' => $full['Cell No'] ?? '',
                ];
            }
        }

        fclose($handle);

        $this->previewRows = $rows;
        $this->totalRows = $count;
        $this->showPreview = true;
    }

    public function startImport(): void
    {
        if (! $this->storedPath) {
            return;
        }

        $import = MemberImport::create([
            'type' => 'members',
            'file_path' => $this->storedPath,
            'import_mode' => $this->importMode,
            'status' => 'pending',
            'total_rows' => $this->totalRows,
        ]);

        ImportMembersJob::dispatch($import);

        $this->importId = $import->id;
        $this->storedPath = null;
        $this->showPreview = false;
        $this->csvFile = null;
    }

    public function pollStatus(): void
    {
        // Triggered by wire:poll — just re-render to pick up latest import state
    }

    public function render(): \Illuminate\View\View
    {
        $activeImport = $this->importId
            ? MemberImport::find($this->importId)
            : null;

        return view('livewire.admin.members.import', [
            'activeImport' => $activeImport,
        ]);
    }
}
