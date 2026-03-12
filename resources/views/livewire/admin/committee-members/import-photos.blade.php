<div class="space-y-6">
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.committee-members.index')" wire:navigate>Back</flux:button>
        <flux:heading size="xl">Import Committee Photos</flux:heading>
    </div>

    @if ($processed)
        <flux:card class="max-w-2xl space-y-4">
            <div class="flex items-center gap-3">
                <flux:icon icon="check-circle" class="text-green-500" />
                <flux:heading size="lg">Photos Imported</flux:heading>
            </div>

            <div class="grid grid-cols-2 gap-4 text-center">
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $matched }}</div>
                    <flux:text class="text-zinc-500 text-sm">Photos Matched</flux:text>
                </div>
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div class="text-2xl font-bold text-zinc-500">{{ $unmatched }}</div>
                    <flux:text class="text-zinc-500 text-sm">Not Matched</flux:text>
                </div>
            </div>

            @if (count($unmatchedFiles) > 0)
                <div>
                    <flux:heading size="sm" class="mb-2">Unmatched files (no member found)</flux:heading>
                    <div class="rounded border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800 max-h-48 overflow-y-auto">
                        @foreach ($unmatchedFiles as $file)
                            <div class="px-3 py-2 text-sm text-zinc-500 font-mono">{{ $file }}</div>
                        @endforeach
                    </div>
                    @if ($unmatched > 20)
                        <flux:text class="text-xs text-zinc-400 mt-1">Showing 20 of {{ $unmatched }} unmatched files.</flux:text>
                    @endif
                </div>
            @endif

            <div class="flex gap-2">
                <flux:button variant="primary" :href="route('admin.committee-members.index')" wire:navigate>View Committee</flux:button>
                <flux:button variant="ghost" wire:click="$set('processed', false)">Import More</flux:button>
            </div>
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
                        <span wire:loading.remove wire:target="startImport">Import Photos</span>
                        <span wire:loading wire:target="startImport">Processing...</span>
                    </flux:button>
                    <flux:button variant="ghost" :href="route('admin.committee-members.index')" wire:navigate>Cancel</flux:button>
                </div>
            @endif
        </flux:card>
    @endif
</div>
