<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDatabase extends Command
{
    protected $signature = 'db:import
                            {file : Path to the SQL file to import}
                            {--drop-tables : Drop all existing tables before import}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Import PostgreSQL database from a SQL file';

    public function handle(): int
    {
        // Temporarily switch to array session driver to prevent database session errors
        $originalSessionDriver = config('session.driver');
        config(['session.driver' => 'array']);

        try {
            return $this->performImport();
        } finally {
            // Restore original session driver
            config(['session.driver' => $originalSessionDriver]);
        }
    }

    protected function performImport(): int
    {
        $filePath = $this->argument('file');
        $dropTables = $this->option('drop-tables');
        $force = $this->option('force');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info('Starting database import...');
        $this->line("File: {$filePath}");

        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        // Confirm destructive action (only if not forced and running in CLI)
        if (! $force && $this->input->isInteractive()) {
            if ($dropTables) {
                if (! $this->confirm('⚠️  This will DROP ALL TABLES in the database. Are you sure?', false)) {
                    $this->info('Import cancelled.');

                    return self::SUCCESS;
                }
            } elseif (! $this->confirm('This will import data into the existing database. Continue?', true)) {
                $this->info('Import cancelled.');

                return self::SUCCESS;
            }
        }

        if ($dropTables) {
            $this->dropAllTables();
        }

        // Build the psql command
        $pgPassword = $password ? "PGPASSWORD='{$password}' " : '';
        $psqlPath = $this->findPsql();
        $command = "{$pgPassword}{$psqlPath} -h {$host} -p {$port} -U {$username} -d {$database} -f {$filePath} 2>&1";

        $output = [];
        $exitCode = 0;

        $this->line('Importing database...');
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Database import failed!');
            if (! empty($output)) {
                $this->line(implode("\n", array_slice($output, -20))); // Show last 20 lines
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Database imported successfully!');

        // Run migrations to ensure sessions table and other system tables exist
        $this->newLine();
        $this->line('Running migrations to ensure system tables exist...');
        $migrateExitCode = \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--force' => true,
        ]);

        if ($migrateExitCode === 0) {
            $this->info('✓ Migrations completed successfully!');
        } else {
            $this->warn('⚠ Migrations completed with warnings. Please check manually.');
        }

        return self::SUCCESS;
    }

    protected function dropAllTables(): void
    {
        $this->line('Dropping all tables...');

        // Get all table names
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        if (empty($tables)) {
            return;
        }

        // Disable foreign key checks and drop all tables
        DB::statement('SET session_replication_role = replica;');

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS \"{$table->tablename}\" CASCADE");
        }

        DB::statement('SET session_replication_role = DEFAULT;');

        $this->info('All tables dropped.');
    }

    protected function findPsql(): string
    {
        // Common locations for psql
        $possiblePaths = [
            '/Users/Shared/DBngin/postgresql/17.0/bin/psql', // DBngin
            '/opt/homebrew/bin/psql', // Homebrew Apple Silicon
            '/usr/local/bin/psql', // Homebrew Intel
            '/usr/bin/psql', // System
            'psql', // PATH fallback
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'psql' || file_exists($path)) {
                return $path;
            }
        }

        return 'psql'; // Fallback to PATH
    }
}
