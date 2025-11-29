<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportDatabase extends Command
{
    protected $signature = 'db:export
                            {--format=sql : Export format (sql or dump)}
                            {--filename= : Custom filename for the export}
                            {--path=backups : Storage path for the export file}
                            {--disk= : Storage disk to use (local, s3, etc.)}';

    protected $description = 'Export PostgreSQL database to a file';

    public function handle(): int
    {
        $format = $this->option('format');
        $filename = $this->option('filename') ?? $this->generateFilename($format);
        $path = $this->option('path');
        $disk = $this->option('disk') ?? $this->getDefaultDisk();

        $this->info('Starting database export...');
        $this->line("Storage disk: {$disk}");

        // Create temporary file for export
        $tempFile = tempnam(sys_get_temp_dir(), 'db_export_');
        $outputFile = $tempFile.'.'.($format === 'dump' ? 'dump' : 'sql');
        rename($tempFile, $outputFile);

        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $command = $this->buildCommand($format, $host, $port, $database, $username, $password, $outputFile);

        $this->line("Exporting database: {$database}");

        $exitCode = 0;
        $output = [];
        exec($command.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($outputFile) || filesize($outputFile) === 0) {
            @unlink($outputFile);
            $this->error('Database export failed!');
            if (! empty($output)) {
                $this->line(implode("\n", $output));
            }

            return self::FAILURE;
        }

        // Upload to storage
        $storagePath = "{$path}/{$filename}";
        $fileContents = file_get_contents($outputFile);
        Storage::disk($disk)->put($storagePath, $fileContents);

        $size = $this->formatBytes(filesize($outputFile));
        @unlink($outputFile);

        $this->newLine();
        $this->info('✓ Database exported successfully!');
        $this->line("Storage: {$disk}");
        $this->line("Path: {$storagePath}");
        $this->line("Size: {$size}");

        return self::SUCCESS;
    }

    protected function getDefaultDisk(): string
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

    protected function buildCommand(string $format, string $host, string $port, string $database, string $username, ?string $password, string $outputFile): string
    {
        $pgPassword = $password ? "PGPASSWORD='{$password}' " : '';

        if ($format === 'dump') {
            return "{$pgPassword}pg_dump -h {$host} -p {$port} -U {$username} -d {$database} -Fc -f {$outputFile}";
        }

        return "{$pgPassword}pg_dump -h {$host} -p {$port} -U {$username} -d {$database} -f {$outputFile}";
    }

    protected function generateFilename(string $format): string
    {
        $database = config('database.connections.pgsql.database');
        $timestamp = now()->format('Y-m-d_His');
        $extension = $format === 'dump' ? 'dump' : 'sql';

        return "{$database}_{$timestamp}.{$extension}";
    }

    protected function formatBytes(int $bytes): string
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
}
