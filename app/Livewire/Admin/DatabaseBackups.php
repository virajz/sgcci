<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class DatabaseBackups extends Component
{
    use WithFileUploads;

    public bool $isExporting = false;

    public bool $isImporting = false;

    public bool $showImportModal = false;

    public bool $showRestoreModal = false;

    public ?string $restoreFilename = null;

    public bool $dropTables = false;

    public string $format = 'sql';

    public ?TemporaryUploadedFile $uploadedFile = null;

    public function export(): void
    {
        $this->isExporting = true;

        try {
            $exitCode = Artisan::call('db:export', [
                '--format' => $this->format,
            ]);

            if ($exitCode === 0) {
                $this->dispatch('backup-created');
            } else {
                $output = Artisan::output();
                throw new \Exception('Export failed: '.$output);
            }

            $this->isExporting = false;
        } catch (\Exception $e) {
            $this->isExporting = false;
            $this->dispatch('backup-failed', message: $e->getMessage());
        }
    }

    public function download(string $filename): StreamedResponse
    {
        $disk = $this->getStorageDisk();
        $path = "backups/{$filename}";

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Backup file not found');
        }

        return response()->streamDownload(function () use ($disk, $path) {
            echo Storage::disk($disk)->get($path);
        }, $filename);
    }

    public function delete(string $filename): void
    {
        $disk = $this->getStorageDisk();
        $path = "backups/{$filename}";

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
            $this->dispatch('backup-deleted');
        }
    }

    public function restore(string $filename): void
    {
        $this->restoreFilename = $filename;
        $this->dropTables = false;
        $this->showRestoreModal = true;
    }

    public function confirmRestore(): void
    {
        if (! $this->restoreFilename) {
            return;
        }

        $this->isImporting = true;

        try {
            $disk = $this->getStorageDisk();
            $path = "backups/{$this->restoreFilename}";

            if (! Storage::disk($disk)->exists($path)) {
                throw new \Exception('Backup file not found');
            }

            // Download file to temp location
            $tempFile = tempnam(sys_get_temp_dir(), 'db_import_');
            file_put_contents($tempFile, Storage::disk($disk)->get($path));

            // Run import command with --force to skip interactive prompts
            $exitCode = Artisan::call('db:import', [
                'file' => $tempFile,
                '--drop-tables' => $this->dropTables,
                '--force' => true,
            ]);

            @unlink($tempFile);

            if ($exitCode === 0) {
                // Migrations were run by the import command, session table now exists
                $this->showRestoreModal = false;
                $this->restoreFilename = null;
                $this->dispatch('import-success');
            } else {
                throw new \Exception('Import command failed');
            }
        } catch (\Exception $e) {
            $this->dispatch('import-failed', message: $e->getMessage());
        } finally {
            $this->isImporting = false;
        }
    }

    public function importUpload(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|mimes:sql,dump|max:512000', // 500MB max
        ]);

        $this->isImporting = true;

        try {
            // Save uploaded file to temp location
            $tempFile = $this->uploadedFile->getRealPath();

            // Run import command with --force to skip interactive prompts
            $exitCode = Artisan::call('db:import', [
                'file' => $tempFile,
                '--drop-tables' => $this->dropTables,
                '--force' => true,
            ]);

            if ($exitCode === 0) {
                // Migrations were run by the import command, session table now exists
                $this->showImportModal = false;
                $this->uploadedFile = null;
                $this->dispatch('import-success');
            } else {
                throw new \Exception('Import command failed');
            }
        } catch (\Exception $e) {
            $this->dispatch('import-failed', message: $e->getMessage());
        } finally {
            $this->isImporting = false;
        }
    }

    public function openImportModal(): void
    {
        $this->uploadedFile = null;
        $this->dropTables = false;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->uploadedFile = null;
        $this->dropTables = false;
    }

    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->restoreFilename = null;
        $this->dropTables = false;
    }

    public function getBackupsProperty(): array
    {
        $disk = $this->getStorageDisk();
        $files = Storage::disk($disk)->files('backups');

        return collect($files)
            ->filter(fn ($file) => str_ends_with($file, '.sql') || str_ends_with($file, '.dump'))
            ->map(function ($file) use ($disk) {
                return [
                    'name' => basename($file),
                    'size' => Storage::disk($disk)->size($file),
                    'date' => Storage::disk($disk)->lastModified($file),
                    'path' => $file,
                    'disk' => $disk,
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function getStorageDiskProperty(): string
    {
        return $this->getStorageDisk();
    }

    protected function getStorageDisk(): string
    {
        // Use S3 if configured, otherwise use local
        if ($this->isS3Configured()) {
            return 's3';
        }

        return 'local';
    }

    protected function isS3Configured(): bool
    {
        return ! empty(config('filesystems.disks.s3.key'))
            && ! empty(config('filesystems.disks.s3.secret'))
            && ! empty(config('filesystems.disks.s3.bucket'));
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function render(): mixed
    {
        return view('livewire.admin.database-backups');
    }
}
