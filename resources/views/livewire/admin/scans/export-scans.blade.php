<div>
    <flux:button wire:click="openModal" icon="arrow-down-tray">
        Export Scans
    </flux:button>

    <flux:modal wire:model="showModal" class="min-w-[36rem]">
        <form wire:submit="export">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Export Entry Scans</flux:heading>
                    <flux:text class="mt-2">Generate a CSV of all entry/exit records.</flux:text>
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>From Date</flux:label>
                        <flux:date-picker wire:model.live="dateFrom" />
                    </flux:field>
                    <flux:field>
                        <flux:label>To Date</flux:label>
                        <flux:date-picker wire:model.live="dateTo" />
                    </flux:field>
                </div>

                {{-- Entry filter --}}
                <flux:field>
                    <flux:label badge="optional">Status Filter</flux:label>
                    <flux:select wire:model.live="entryFilter" variant="listbox" placeholder="All visitors">
                        <flux:select.option value="">All visitors</flux:select.option>
                        <flux:select.option value="inside">Currently inside</flux:select.option>
                        <flux:select.option value="exited">Exited</flux:select.option>
                    </flux:select>
                </flux:field>

                {{-- Options --}}
                <flux:field>
                    <flux:label badge="optional">Include in Export</flux:label>
                    <div class="mt-2">
                        <flux:checkbox wire:model="includeExitTime" label="Exit time & currently inside status" />
                    </div>
                </flux:field>

                {{-- Count --}}
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <flux:text class="font-medium">Records to export:</flux:text>
                        <flux:badge size="lg" color="zinc" variant="solid">
                            {{ number_format($resultCount) }}
                        </flux:badge>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="arrow-down-tray" iconVariant="outline"
                        :disabled="$resultCount === 0">
                        Export CSV
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
