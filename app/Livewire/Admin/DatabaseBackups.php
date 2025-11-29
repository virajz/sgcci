<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class DatabaseBackups extends Component
{
    public bool $isExporting = false;

    public string $format = 'sql';

    public function export(): void
    {
        $this->isExporting = true;

        try {
            Artisan::call('db:export', [
                '--format' => $this->format,
            ]);

            $this->dispatch('backup-created');
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
