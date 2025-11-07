<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Support Tickets</flux:heading>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Search by ticket number, booking code, or brand name..." icon="magnifying-glass"
            iconVariant="outline" class="md:max-w-md" />

        <flux:select wire:model.live="statusFilter" class="w-full md:w-48">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </flux:select>
    </div>

    <flux:card class="overflow-hidden">
        @if ($this->tickets->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Ticket Number</flux:table.column>
                        <flux:table.column>Booking Code</flux:table.column>
                        <flux:table.column>Brand Name</flux:table.column>
                        <flux:table.column>Documents</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->tickets as $ticket)
                            <flux:table.row :key="$ticket->id">
                                <flux:table.cell>
                                    <flux:text class="font-mono">{{ $ticket->ticket_number }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="font-mono">{{ $ticket->booking->booking_code }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text>{{ $ticket->booking->brand_name }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge variant="outline">{{ count($ticket->documents ?? []) }} files
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $statusColor = match ($ticket->status) {
                                            'pending' => 'yellow',
                                            'approved' => 'green',
                                            'rejected' => 'red',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <flux:badge :color="$statusColor">{{ ucfirst($ticket->status) }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm">{{ $ticket->created_at->format('M d, Y') }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="sm" :href="route('admin.support-tickets.show', $ticket)"
                                        wire:navigate>
                                        View
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-8 text-center">
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">No support tickets found
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if ($this->tickets->hasPages())
                <div class="mt-4">
                    {{ $this->tickets->links() }}
                </div>
            @endif
        @else
            <div class="py-12 text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">No support tickets found</flux:text>
            </div>
        @endif
    </flux:card>
</div>
