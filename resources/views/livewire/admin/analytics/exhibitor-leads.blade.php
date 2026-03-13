<div class="space-y-6">
    <flux:heading size="xl">Exhibitor Leads</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by exhibitor name..."
        icon="magnifying-glass" iconVariant="outline" class="max-w-md" />

    @if ($bookings->count() > 0)
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                        wire:click="sort('name')">Exhibitor</flux:table.column>
                    <flux:table.column>Stalls</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'leads'" :direction="$sortDirection"
                        wire:click="sort('leads')">Leads</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'whatsapp'" :direction="$sortDirection"
                        wire:click="sort('whatsapp')">WhatsApp Inquiries</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($bookings as $booking)
                        <flux:table.row :key="$booking['id']">
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $booking['brand_name'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($booking['selected_stalls'] as $stall)
                                        <flux:badge size="sm" color="zinc">{{ $stall }}</flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $booking['leads_count'] > 0 ? 'green' : 'zinc' }}" variant="pill">
                                    {{ $booking['leads_count'] }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $booking['whatsapp_count'] > 0 ? 'blue' : 'zinc' }}"
                                    variant="pill">
                                    {{ $booking['whatsapp_count'] }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <div class="py-12 text-center">
            <flux:icon.chart-bar class="w-12 h-12 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mb-1">No exhibitors found</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                @if ($search)
                    No exhibitors match your search.
                @else
                    Exhibitor lead data will appear here once exhibitors are active.
                @endif
            </flux:text>
        </div>
    @endif
</div>
