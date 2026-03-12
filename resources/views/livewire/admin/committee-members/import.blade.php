<div class="space-y-6"
    @if($activeImport && !$activeImport->isFinished())
        wire:poll.2s="pollStatus"
    @endif
>
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.committee-members.index')" wire:navigate>Back</flux:button>
        <flux:heading size="xl">Import Committee Members</flux:heading>
    </div>

    {{-- Progress Panel --}}
    @if ($activeImport)
        <flux:card class="max-w-2xl space-y-4">
            @if ($activeImport->status === 'pending' || $activeImport->status === 'processing')
                <div class="flex items-center gap-3">
                    <flux:icon icon="arrow-path" class="animate-spin text-blue-500" />
                    <flux:heading size="lg">Importing...</flux:heading>
                </div>
                <flux:text class="text-zinc-500">
                    Processing {{ number_format($activeImport->processed_rows) }} of {{ number_format($activeImport->total_rows) }} rows
                </flux:text>

                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                    <div class="bg-blue-500 h-3 rounded-full transition-all duration-500"
                        style="width: {{ $activeImport->progressPercent() }}%"></div>
                </div>
                <flux:text class="text-sm text-zinc-500">{{ $activeImport->progressPercent() }}% complete</flux:text>

            @elseif ($activeImport->status === 'completed')
                <div class="flex items-center gap-3">
                    <flux:icon icon="check-circle" class="text-green-500" />
                    <flux:heading size="lg">Import Complete</flux:heading>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div class="text-2xl font-bold text-black dark:text-white">{{ number_format($activeImport->total_rows) }}</div>
                        <flux:text class="text-zinc-500 text-sm">Total Rows</flux:text>
                    </div>
                    <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($activeImport->imported_rows) }}</div>
                        <flux:text class="text-zinc-500 text-sm">Imported</flux:text>
                    </div>
                    <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div class="text-2xl font-bold text-zinc-500">{{ number_format($activeImport->skipped_rows) }}</div>
                        <flux:text class="text-zinc-500 text-sm">Skipped</flux:text>
                    </div>
                </div>
                <flux:button variant="primary" :href="route('admin.committee-members.index')" wire:navigate>View Committee</flux:button>

            @elseif ($activeImport->status === 'failed')
                <div class="flex items-center gap-3">
                    <flux:icon icon="x-circle" class="text-red-500" />
                    <flux:heading size="lg">Import Failed</flux:heading>
                </div>
                @if ($activeImport->error_message)
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        {{ $activeImport->error_message }}
                    </flux:callout>
                @endif
                <flux:button variant="ghost" wire:click="$set('importId', null)">Try Again</flux:button>
            @endif
        </flux:card>

    @else
        <flux:card class="max-w-2xl space-y-6">
            <div>
                <flux:heading size="lg">Upload CSV</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    The CSV must have these columns:
                    <code class="text-sm font-mono bg-zinc-100 dark:bg-zinc-800 px-1 rounded">Sr No, Mem No, Name, Post, Post for Badges 1, Mobile</code>
                </flux:text>
            </div>

            <flux:field>
                <flux:label>CSV File</flux:label>
                <flux:input type="file" wire:model="csvFile" accept=".csv,.txt" />
                <flux:error name="csvFile" />
            </flux:field>

            <flux:field>
                <flux:label>If membership number already exists</flux:label>
                <flux:select wire:model="importMode">
                    <flux:select.option value="skip">Skip (keep existing)</flux:select.option>
                    <flux:select.option value="overwrite">Overwrite (update existing)</flux:select.option>
                </flux:select>
            </flux:field>

            <div wire:loading wire:target="csvFile" class="flex items-center gap-2 text-zinc-500 text-sm">
                <flux:icon icon="arrow-path" class="animate-spin" size="sm" />
                Reading file...
            </div>

            @if ($showPreview && count($previewRows) > 0)
                <div>
                    <flux:heading size="sm" class="mb-2">Preview (first {{ count($previewRows) }} of {{ $totalRows }} rows)</flux:heading>
                    <div class="overflow-x-auto rounded border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    @foreach (array_keys($previewRows[0]) as $col)
                                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($previewRows as $row)
                                    <tr class="bg-white dark:bg-zinc-900">
                                        @foreach ($row as $cell)
                                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-300 max-w-xs truncate">{{ $cell ?: '—' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:button variant="primary" wire:click="startImport" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="startImport">Queue Import ({{ $totalRows }} rows)</span>
                        <span wire:loading wire:target="startImport">Queuing...</span>
                    </flux:button>
                    <flux:button variant="ghost" :href="route('admin.committee-members.index')" wire:navigate>Cancel</flux:button>
                </div>
            @endif
        </flux:card>
    @endif
</div>
