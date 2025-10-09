<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Booking Inquiries</flux:heading>
        <livewire:admin.stall-block-manager />
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:tabs wire:model.live="statusFilter" variant="segmented">
            <flux:tab name="all">All</flux:tab>
            <flux:tab name="pending_approval">Pending</flux:tab>
            <flux:tab name="approved_by_admin">Approved</flux:tab>
            <flux:tab name="payment_pending">Payment Pending</flux:tab>
            <flux:tab name="payment_completed">Completed</flux:tab>
            <flux:tab name="manual_block">Manual Block</flux:tab>
            <flux:tab name="rejected">Rejected</flux:tab>
        </flux:tabs>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code, brand, contact, or email..."
            icon="magnifying-glass" iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($bookings->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Booking Code</flux:table.column>
                        <flux:table.column>Brand Name</flux:table.column>
                        <flux:table.column>Contact Person</flux:table.column>
                        <flux:table.column>Stalls</flux:table.column>
                        <flux:table.column>Amount</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($bookings as $booking)
                            <flux:table.row :key="$booking->id">
                                <flux:table.cell>
                                    <a href="{{ route('admin.inquiries.show', $booking) }}"
                                        class="font-semibold text-black dark:text-white" wire:navigate>
                                        {{ $booking->booking_code }}
                                    </a>
                                </flux:table.cell>

                                <flux:table.cell>{{ $booking->brand_name }}</flux:table.cell>

                                <flux:table.cell>
                                    <div>{{ $booking->contact_person }}</div>
                                    <div class="text-xs text-zinc-500">{{ $booking->email }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($booking->selected_stalls as $stall)
                                            <flux:badge variant="outline" size="sm">{{ $stall }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-semibold">
                                        @if ($booking->is_manual_block)
                                            ₹0.00
                                        @else
                                            ₹{{ number_format((float) $booking->total_with_gst, 2) }}
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($booking->is_manual_block)
                                        <flux:badge color="zinc" variant="solid">
                                            Manual Block
                                        </flux:badge>
                                    @else
                                        <flux:badge :color="$booking->status->color()" variant="solid">
                                            {{ $booking->status->label() }}
                                        </flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="text-sm">{{ $booking->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-zinc-500">{{ $booking->created_at->format('h:i A') }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($booking->is_manual_block)
                                        <flux:button size="sm" variant="ghost" icon="lock-open"
                                            class="cursor-pointer" iconVariant="outline"
                                            wire:click="releaseStall({{ $booking->id }})"
                                            wire:confirm="Are you sure you want to release this manually blocked stall?">
                                            Release
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="ghost" icon="eye" iconVariant="outline"
                                            href="{{ route('admin.inquiries.show', $booking) }}" wire:navigate>
                                            View
                                        </flux:button>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="p-4 border-t dark:border-zinc-700">
                {{ $bookings->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon.inbox class="w-12 h-12 mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">No inquiries found</flux:heading>
                <flux:text class="text-zinc-500">
                    @if ($search || $statusFilter !== 'all')
                        Try adjusting your filters
                    @else
                        New booking inquiries will appear here
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>
</div>
