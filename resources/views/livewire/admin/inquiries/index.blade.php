<div class="space-y-6" x-data="{ statusFilter: @entangle('statusFilter').live }">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Booking Inquiries</flux:heading>
        <div class="flex items-center gap-2">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" icon="view-columns" iconVariant="outline">
                    Columns
                </flux:button>

                <flux:menu class="min-w-48">
                    @foreach ($visibleColumns as $column => $isVisible)
                        <flux:menu.checkbox wire:model.live="visibleColumns.{{ $column }}">
                            {{ $this->getColumnLabel($column) }}
                        </flux:menu.checkbox>
                    @endforeach

                    <flux:menu.separator />

                    <flux:menu.item wire:click="resetColumns" icon="arrow-path" iconVariant="outline">
                        Reset to Default
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <livewire:admin.inquiries.export-bookings />
            <livewire:admin.stall-block-manager />
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:tabs x-model="statusFilter" variant="segmented">
            <flux:tab name="all">All</flux:tab>
            <flux:tab name="pending_approval">Pending</flux:tab>
            <flux:tab name="approved_by_admin">Approved</flux:tab>
            <flux:tab name="payment_pending">Payment Pending</flux:tab>
            <flux:tab name="payment_completed">Completed</flux:tab>
            <flux:tab name="manual_block">Manual Block</flux:tab>
            <flux:tab name="rejected">Rejected</flux:tab>
            <flux:tab name="expired">Expired</flux:tab>
        </flux:tabs>

        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Search by code, brand, contact, phone, or email..." icon="magnifying-glass"
            iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($bookings->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        @if ($visibleColumns['booking_code'])
                            <flux:table.column>Booking Code</flux:table.column>
                        @endif
                        @if ($visibleColumns['brand_name'])
                            <flux:table.column>Brand Name</flux:table.column>
                        @endif
                        @if ($visibleColumns['contact_person'])
                            <flux:table.column>Contact Person</flux:table.column>
                        @endif
                        @if ($visibleColumns['phone'])
                            <flux:table.column>Phone</flux:table.column>
                        @endif
                        @if ($visibleColumns['membership'])
                            <flux:table.column>Membership</flux:table.column>
                        @endif
                        @if ($visibleColumns['stalls'])
                            <flux:table.column>Stalls</flux:table.column>
                        @endif
                        @if ($visibleColumns['amount'])
                            <flux:table.column>Amount</flux:table.column>
                        @endif
                        @if ($visibleColumns['part_payment'])
                            <flux:table.column>Part Payment</flux:table.column>
                        @endif
                        @if ($visibleColumns['status'])
                            <flux:table.column>Status</flux:table.column>
                        @endif
                        @if ($visibleColumns['date'])
                            <flux:table.column>Date</flux:table.column>
                        @endif
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($bookings as $booking)
                            <flux:table.row :key="$booking->id">
                                @if ($visibleColumns['booking_code'])
                                    <flux:table.cell>
                                        <a href="{{ route('admin.inquiries.show', $booking) }}"
                                            class="font-semibold text-black dark:text-white" wire:navigate>
                                            {{ $booking->booking_code }}
                                        </a>
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['brand_name'])
                                    <flux:table.cell>{{ $booking->brand_name }}</flux:table.cell>
                                @endif

                                @if ($visibleColumns['contact_person'])
                                    <flux:table.cell>
                                        <div>{{ $booking->contact_person }}</div>
                                        <div class="text-xs text-zinc-500">{{ $booking->email }}</div>
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['phone'])
                                    <flux:table.cell>
                                        <div class="text-sm">{{ $booking->phone_code }} {{ $booking->phone_number }}
                                        </div>
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['membership'])
                                    <flux:table.cell>
                                        @if ($booking->is_sgcci_member && $booking->membership_number)
                                            <div class="text-sm">
                                                <div class="font-medium">{{ $booking->membership_number }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ ucwords(str_replace('-', ' ', $booking->membership_type)) }}
                                                </div>
                                            </div>
                                        @elseif ($booking->is_sgcci_member)
                                            <div class="text-xs text-zinc-500">
                                                {{ ucwords(str_replace('-', ' ', $booking->membership_type)) }}</div>
                                        @else
                                            <div class="text-xs text-zinc-400">-</div>
                                        @endif
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['stalls'])
                                    <flux:table.cell>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($booking->selected_stalls as $stall)
                                                <flux:badge variant="outline" size="sm">{{ $stall }}
                                                </flux:badge>
                                            @endforeach
                                        </div>
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['amount'])
                                    <flux:table.cell>
                                        <div class="font-semibold">
                                            @if ($booking->is_manual_block)
                                                ₹0.00
                                            @else
                                                ₹{{ number_format((float) $booking->total_with_gst, 2) }}
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['part_payment'])
                                    <flux:table.cell>
                                        @if ($booking->is_manual_block)
                                            <div class="text-xs text-zinc-400">-</div>
                                        @elseif ($booking->amount_paid > 0)
                                            <div class="text-sm">
                                                <div class="font-medium text-green-600 dark:text-green-400">
                                                    ₹{{ number_format((float) $booking->amount_paid, 2) }}
                                                </div>
                                                @if ($booking->remaining_amount > 0)
                                                    <div class="text-xs text-zinc-500">
                                                        {{ number_format($booking->getPaymentPercentage(), 0) }}% paid
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-xs text-zinc-400">Not paid</div>
                                        @endif
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['status'])
                                    <flux:table.cell>
                                        @if ($booking->is_manual_block)
                                            <flux:badge color="zinc" variant="solid">
                                                Manual Block
                                            </flux:badge>
                                        @else
                                            <flux:badge :color="$booking->status->color()" size="sm"
                                                variant="solid">
                                                {{ $booking->status->label() }}
                                            </flux:badge>
                                        @endif
                                    </flux:table.cell>
                                @endif

                                @if ($visibleColumns['date'])
                                    <flux:table.cell>
                                        <div class="text-sm">{{ $booking->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-zinc-500">{{ $booking->created_at->format('h:i A') }}
                                        </div>
                                    </flux:table.cell>
                                @endif

                                <flux:table.cell>
                                    @if ($booking->is_manual_block)
                                        <flux:button size="sm" variant="ghost" icon="lock-open"
                                            class="cursor-pointer" iconVariant="outline"
                                            wire:click="confirmRelease({{ $booking->id }})">
                                            Release
                                        </flux:button>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <flux:button size="sm" variant="ghost" icon="eye"
                                                iconVariant="outline"
                                                href="{{ route('admin.inquiries.show', $booking) }}" wire:navigate>
                                                View
                                            </flux:button>

                                            @if ($booking->status === \App\BookingStatus::PaymentPending && $booking->payment_link)
                                                <flux:dropdown position="bottom" align="end">
                                                    <flux:button size="sm" variant="ghost"
                                                        icon="ellipsis-horizontal" iconVariant="outline" />

                                                    <flux:menu>
                                                        <flux:menu.item icon="link" x-data
                                                            x-on:click="navigator.clipboard.writeText('{{ $booking->payment_link }}').then(() => {
                                                                $flux.toast({
                                                                    variant: 'success',
                                                                    heading: 'Copied!',
                                                                    text: 'Payment link copied to clipboard'
                                                                });
                                                            })">
                                                            Copy Payment Link
                                                        </flux:menu.item>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            @endif
                                        </div>
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

    {{-- Confirmation Modal for Release --}}
    <flux:modal wire:model="showConfirmReleaseModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Release Stall?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to release this manually blocked stall.</p>
                    <p>This action will make the stall available for booking again.</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="releaseStall" variant="danger">Release Stall</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
