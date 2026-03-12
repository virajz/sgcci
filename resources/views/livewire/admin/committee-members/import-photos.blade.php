<div class="space-y-6"
    @if($activeImport && !$activeImport->isFinished())
        wire:poll.2s="pollStatus"
    @endif
>
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.committee-members.index')" wire:navigate>Back</flux:button>
        <flux:heading size="xl">Import Committee Photos</flux:heading>
    </div>

    {{-- Progress Panel --}}
    @if ($activeImport)
        <flux:card class="max-w-2xl space-y-4">
            @if ($activeImport->status === 'pending' || $activeImport->status === 'processing')
                <div class="flex items-center gap-3">
                    <flux:icon icon="arrow-path" class="animate-spin text-blue-500" />
                    <flux:heading size="lg">Importing Photos...</flux:heading>
                </div>
                <flux:text class="text-zinc-500">
                    Processed {{ number_format($activeImport->processed_rows) }} photos so far
                </flux:text>
                <div class="flex gap-4 text-sm">
                    <span class="text-green-600 dark:text-green-400 font-medium">{{ number_format($activeImport->imported_rows) }} matched</span>
                    <span class="text-zinc-500">{{ number_format($activeImport->skipped_rows) }} unmatched</span>
                </div>

            @elseif ($activeImport->status === 'completed')
                <div class="flex items-center gap-3">
                    <flux:icon icon="check-circle" class="text-green-500" />
                    <flux:heading size="lg">Photos Imported</flux:heading>
                </div>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($activeImport->imported_rows) }}</div>
                        <flux:text class="text-zinc-500 text-sm">Photos Matched</flux:text>
                    </div>
                    <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div class="text-2xl font-bold text-zinc-500">{{ number_format($activeImport->skipped_rows) }}</div>
                        <flux:text class="text-zinc-500 text-sm">Not Matched</flux:text>
                    </div>
                </div>
                <div class="flex gap-2">
                    <flux:button variant="primary" :href="route('admin.committee-members.index')" wire:navigate>View Committee</flux:button>
                    <flux:button variant="ghost" wire:click="$set('importId', null)">Import More</flux:button>
                </div>

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
                <flux:heading size="lg">Upload ZIP File</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Upload a ZIP containing member photos. Each file must be named after the member's
                    <strong>Membership Number</strong> — e.g.
                    <code class="text-sm font-mono bg-zinc-100 dark:bg-zinc-800 px-1 rounded">L1133.jpg</code>,
                    <code class="text-sm font-mono bg-zinc-100 dark:bg-zinc-800 px-1 rounded">CP2.jpg</code>.
                    Files with no matching member will be skipped.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>ZIP File <flux:text class="text-zinc-400 text-sm">(max 100 MB)</flux:text></flux:label>
                <flux:input type="file" wire:model="zipFile" accept=".zip" />
                <flux:error name="zipFile" />
            </flux:field>

            <div wire:loading wire:target="zipFile" class="flex items-center gap-2 text-zinc-500 text-sm">
                <flux:icon icon="arrow-path" class="animate-spin" size="sm" />
                Uploading...
            </div>

            @if ($storedPath && !$errors->has('zipFile'))
                <div class="flex gap-2">
                    <flux:button variant="primary" wire:click="startImport" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="startImport">Queue Import</span>
                        <span wire:loading wire:target="startImport">Queuing...</span>
                    </flux:button>
                    <flux:button variant="ghost" :href="route('admin.committee-members.index')" wire:navigate>Cancel</flux:button>
                </div>
            @endif
        </flux:card>
    @endif
</div>
