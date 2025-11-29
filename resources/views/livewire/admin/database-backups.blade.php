<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Database Backups</flux:heading>
    </div>

    <flux:card>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Create New Backup</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Export your PostgreSQL database to
                        @if ($this->storageDisk === 's3')
                            <span class="font-medium text-blue-600 dark:text-blue-400">Amazon S3</span>
                        @else
                            <span class="font-medium">local storage</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-end gap-4">
                <flux:field label="Export Format" class="flex-1 max-w-xs">
                    <flux:select wire:model="format">
                        <option value="sql">SQL (Plain Text)</option>
                        <option value="dump">Dump (Compressed)</option>
                    </flux:select>
                </flux:field>

                <flux:button wire:click="export" wire:loading.attr="disabled" variant="primary" icon="arrow-down-tray">
                    <span wire:loading.remove wire:target="export">Create Backup</span>
                    <span wire:loading wire:target="export">Creating...</span>
                </flux:button>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Available Backups</h3>
                <div class="flex items-center gap-2">
                    <flux:button wire:click="openImportModal" variant="primary" size="sm" icon="arrow-up-tray">
                        Import / Restore
                    </flux:button>
                    <flux:button wire:click="$refresh" variant="ghost" size="sm" icon="arrow-path">
                        Refresh
                    </flux:button>
                </div>
            </div>

            @if (count($this->backups) > 0)
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Filename</flux:table.column>
                            <flux:table.column>Size</flux:table.column>
                            <flux:table.column>Created</flux:table.column>
                            <flux:table.column>Actions</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->backups as $backup)
                                <flux:table.row :key="$backup['name']">
                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <flux:icon.document-arrow-down variant="outline"
                                                class="size-5 text-zinc-500" />
                                            <span class="font-mono text-sm">{{ $backup['name'] }}</span>
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $this->formatBytes($backup['size']) }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('M d, Y g:i A') }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <flux:button wire:click="download('{{ $backup['name'] }}')" variant="ghost"
                                                size="sm" icon="arrow-down-tray">
                                                Download
                                            </flux:button>
                                            <flux:button wire:click="restore('{{ $backup['name'] }}')" variant="outline"
                                                size="sm" icon="arrow-path">
                                                Restore
                                            </flux:button>
                                            <flux:button wire:click="delete('{{ $backup['name'] }}')"
                                                wire:confirm="Are you sure you want to delete this backup?"
                                                variant="danger" size="sm" icon="trash">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @else
                <div class="py-12 text-center">
                    <flux:icon.document-arrow-down variant="outline" class="mx-auto size-12 text-zinc-400" />
                    <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">No backups</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Get started by creating your first database backup.
                    </p>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Import/Restore Modal -->
    <flux:modal name="import-modal" :open="$showImportModal" wire:model="showImportModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Import / Restore Database</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Upload a SQL file to restore your database
                </p>
            </div>

            <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="flex-shrink-0 size-5 text-amber-600 dark:text-amber-400" />
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-medium">Warning: This action will modify your database</p>
                        <p class="mt-1">Make sure you have a recent backup before proceeding.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <flux:field label="Upload SQL File">
                    <flux:input type="file" wire:model="uploadedFile" accept=".sql,.dump" />
                    @error('uploadedFile')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:checkbox wire:model="dropTables" label="Drop all existing tables before import">
                    <flux:description>
                        ⚠️ This will completely wipe your database before restoring. Use with caution!
                    </flux:description>
                </flux:checkbox>

                <div wire:loading wire:target="uploadedFile" class="text-sm text-zinc-600 dark:text-zinc-400">
                    Uploading file...
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeImportModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="importUpload" wire:loading.attr="disabled" :disabled="!$uploadedFile"
                    variant="danger">
                    <span wire:loading.remove wire:target="importUpload">Import Database</span>
                    <span wire:loading wire:target="importUpload">Importing...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Restore Confirmation Modal -->
    <flux:modal name="restore-modal" :open="$showRestoreModal" wire:model="showRestoreModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Restore Database Backup</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    You are about to restore: <span class="font-mono font-medium">{{ $restoreFilename }}</span>
                </p>
            </div>

            <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="flex-shrink-0 size-5 text-amber-600 dark:text-amber-400" />
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-medium">Warning: This will modify your database</p>
                        <p class="mt-1">This action will import the backup data into your current database. Any
                            conflicting data may be overwritten.</p>
                    </div>
                </div>
            </div>

            <flux:checkbox wire:model="dropTables" label="Drop all existing tables before restore">
                <flux:description>
                    ⚠️ This will completely wipe your database before restoring. All active sessions will be replaced
                    with those from the backup.
                </flux:description>
            </flux:checkbox>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeRestoreModal" variant="ghost" wire:loading.attr="disabled">
                    Cancel
                </flux:button>
                <flux:button wire:click="confirmRestore" variant="danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmRestore">Restore Database</span>
                    <span wire:loading wire:target="confirmRestore">Restoring...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    @script
        <script>
            $wire.on('backup-created', () => {
                window.Flux.toast({
                    text: 'Database backup created successfully!',
                    variant: 'success'
                });
                $wire.$refresh();
            });

            $wire.on('backup-failed', (event) => {
                window.Flux.toast({
                    text: 'Failed to create backup: ' + event.message,
                    variant: 'danger'
                });
            });

            $wire.on('backup-deleted', () => {
                window.Flux.toast({
                    text: 'Backup deleted successfully',
                    variant: 'success'
                });
                $wire.$refresh();
            });

            $wire.on('import-success', () => {
                // Reload immediately to avoid session errors
                window.location.reload();
            });

            $wire.on('import-failed', (event) => {
                window.Flux.toast({
                    text: 'Import failed: ' + event.message,
                    variant: 'danger',
                    duration: 8000
                });
            });

            $wire.on('prepare-restore', (event) => {
                // When restoring from existing backup, we could show different UI
                // For now, just open the modal
            });
        </script>
    @endscript
</div>
