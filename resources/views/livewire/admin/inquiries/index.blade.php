<div class="space-y-6">
    <flux:heading size="xl" class="mb-6">Booking Inquiries</flux:heading>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code, brand, contact, or email..."
            icon="magnifying-glass" />

        <flux:select wire:model.live="statusFilter" variant="listbox">
            <flux:select.option value="all">All Statuses</flux:select.option>
            <flux:select.option value="pending_approval">Pending Approval</flux:select.option>
            <flux:select.option value="approved_by_admin">Approved by Admin</flux:select.option>
            <flux:select.option value="allotted">Allotted</flux:select.option>
            <flux:select.option value="payment_pending">Payment Pending</flux:select.option>
            <flux:select.option value="payment_completed">Payment Completed</flux:select.option>
            <flux:select.option value="rejected">Rejected</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
        </flux:select>
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
                                        class="font-mono font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        wire:navigate>
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
                                    <div class="font-semibold">₹{{ number_format($booking->total_with_gst, 2) }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge :color="$booking->status->color()" variant="solid">
                                        {{ $booking->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="text-sm">{{ $booking->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-zinc-500">{{ $booking->created_at->format('h:i A') }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:button size="sm" variant="ghost" icon="eye"
                                        href="{{ route('admin.inquiries.show', $booking) }}" wire:navigate>
                                        View
                                    </flux:button>
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
