<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Entry Scans</flux:heading>
        <livewire:admin.scans.export-scans />
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Entered</flux:text>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($scanStats['total_entered']) }}</flux:heading>
                    <flux:text class="mt-1 text-xs text-zinc-500">{{ number_format($scanStats['today']) }} today</flux:text>
                </div>
                <div class="p-3 bg-green-100 rounded-lg dark:bg-green-900/20">
                    <flux:icon.arrow-right-circle class="size-6 text-green-600 dark:text-green-400" variant="outline" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Currently Inside</flux:text>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($scanStats['total_entered'] - $scanStats['total_exited']) }}</flux:heading>
                    <flux:text class="mt-1 text-xs text-zinc-500">Have not exited</flux:text>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg dark:bg-blue-900/20">
                    <flux:icon.users class="size-6 text-blue-600 dark:text-blue-400" variant="outline" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Exited</flux:text>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($scanStats['total_exited']) }}</flux:heading>
                    <flux:text class="mt-1 text-xs text-zinc-500">Marked exit</flux:text>
                </div>
                <div class="p-3 bg-zinc-100 rounded-lg dark:bg-zinc-800">
                    <flux:icon.arrow-left-circle class="size-6 text-zinc-500 dark:text-zinc-400" variant="outline" />
                </div>
            </div>
        </flux:card>

        <flux:card wire:click="toggleOutOfCity" class="cursor-pointer ring-2 {{ $outOfCityFilter === '1' ? 'ring-orange-400' : 'ring-transparent' }} hover:ring-orange-300 transition">
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Out of City</flux:text>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($scanStats['out_of_city']) }}</flux:heading>
                    <flux:text class="mt-1 text-xs text-zinc-500">Not from Surat</flux:text>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg dark:bg-orange-900/20">
                    <flux:icon.map-pin class="size-6 text-orange-600 dark:text-orange-400" variant="outline" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Day-wise chart --}}
    <flux:card>
        <flux:heading size="sm" class="mb-4">Daily Entries — Last 14 Days</flux:heading>
        <flux:chart :value="$dailyScans" class="aspect-[4/1]">
            <flux:chart.svg>
                <flux:chart.bar field="entries" class="text-blue-500 dark:text-blue-400" radius="2" width="80%" />
                <flux:chart.axis axis="x" field="date">
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.cursor type="area" />
            </flux:chart.svg>
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="date" />
                <flux:chart.tooltip.value field="entries" label="Entries" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <flux:field class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name, phone, code…"
                icon="magnifying-glass"
                clearable
            />
        </flux:field>

        <flux:field>
            <flux:date-picker wire:model.live="dateFrom" placeholder="From date" />
        </flux:field>

        <flux:field>
            <flux:date-picker wire:model.live="dateTo" placeholder="To date" />
        </flux:field>

        <flux:field>
            <flux:select wire:model.live="entryFilter" variant="listbox" placeholder="All visitors">
                <flux:select.option value="">All visitors</flux:select.option>
                <flux:select.option value="inside">Currently inside</flux:select.option>
                <flux:select.option value="exited">Exited</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:select wire:model.live="outOfCityFilter" variant="listbox" placeholder="All cities">
                <flux:select.option value="">All cities</flux:select.option>
                <flux:select.option value="1">Out of City</flux:select.option>
            </flux:select>
        </flux:field>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Visitor</flux:table.column>
            <flux:table.column>Company</flux:table.column>
            <flux:table.column>Location</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Entered At</flux:table.column>
            <flux:table.column>Exited At</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($scans as $visitor)
                <flux:table.row :key="$visitor->id">
                    <flux:table.cell>
                        <div>
                            <p class="font-medium text-sm">{{ $visitor->name }}</p>
                            <p class="text-xs text-zinc-500 font-mono">{{ $visitor->phone_number }}</p>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $visitor->company_name ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $visitor->city }}, {{ $visitor->state }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="font-mono text-xs">{{ $visitor->registration_code }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                        {{ $visitor->entered_at->format('d M Y, h:i A') }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                        {{ $visitor->exited_at?->format('d M Y, h:i A') ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($visitor->exited_at)
                            <flux:badge color="zinc" size="sm">Exited</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">Inside</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center py-12 text-zinc-400">
                        No scan records found matching your filters.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $scans->links() }}

</div>
