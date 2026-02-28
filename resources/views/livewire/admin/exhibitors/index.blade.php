<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Exhibitors</flux:heading>
    </div>

    <flux:input wire:model.live.debounce.300ms="search"
        placeholder="Search by code, brand, contact, phone, or email..." icon="magnifying-glass"
        iconVariant="outline" class="max-w-md" />

    <flux:card class="overflow-hidden">
        @if ($bookings->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Booking Code</flux:table.column>
                        <flux:table.column>Brand / Contact</flux:table.column>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>Exhibition</flux:table.column>
                        <flux:table.column>Stalls</flux:table.column>
                        <flux:table.column>Badges</flux:table.column>
                        <flux:table.column>Login Password</flux:table.column>
                        <flux:table.column>Paid At</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($bookings as $booking)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('admin.inquiries.show', $booking) }}"
                                        class="font-mono text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                                        wire:navigate>
                                        {{ $booking->booking_code }}
                                    </a>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-medium">{{ $booking->brand_name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $booking->contact_person }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text class="text-sm">{{ $booking->email }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text class="text-sm">{{ $booking->phone_code }} {{ $booking->phone_number }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text class="text-sm">{{ $booking->exhibition?->title ?? '—' }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($booking->selected_stalls as $stall)
                                            <flux:badge size="sm" color="zinc">{{ $stall }}</flux:badge>
                                        @endforeach
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($editingBadgeLimitId === $booking->id)
                                        <div class="flex items-center gap-2">
                                            <flux:input type="number" wire:model="editingBadgeLimit" min="1" max="100" class="w-20" size="sm" />
                                            <flux:button size="sm" variant="primary" wire:click="saveBadgeLimit" icon="check" />
                                            <flux:button size="sm" variant="ghost" wire:click="cancelEditingBadgeLimit" icon="x-mark" />
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <flux:text class="text-sm">
                                                {{ $booking->badgeMembers->count() }} / {{ $booking->badge_limit }}
                                            </flux:text>
                                            <flux:button size="sm" variant="ghost" icon="pencil" iconVariant="outline"
                                                wire:click="startEditingBadgeLimit({{ $booking->id }}, {{ $booking->badge_limit }})" />
                                        </div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($booking->login_password)
                                        <div class="flex items-center gap-2" x-data>
                                            <flux:text class="font-mono text-sm">{{ $booking->login_password }}</flux:text>
                                            <flux:button size="sm" variant="ghost" icon="clipboard-document" iconVariant="outline"
                                                x-on:click="navigator.clipboard.writeText(
                                                    'Login URL: {{ route('login') }}\nEmail: {{ $booking->email }}\nPhone: {{ $booking->phone_number }}\nPassword: {{ $booking->login_password }}'
                                                ).then(() => {
                                                    $flux.toast({ variant: 'success', heading: 'Copied!', text: 'Login details copied to clipboard' });
                                                })">
                                            </flux:button>
                                        </div>
                                    @else
                                        <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">Not generated</flux:text>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $booking->payment_completed_at?->format('M d, Y') ?? '—' }}
                                    </flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:button size="sm" variant="ghost" icon="identification" iconVariant="outline"
                                        :href="route('admin.exhibitors.badges', $booking)" wire:navigate>
                                        Badges
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $bookings->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon.user-group class="w-12 h-12 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mb-1">No exhibitors found</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    @if ($search)
                        No exhibitors match your search.
                    @else
                        Exhibitors appear here once their payment is completed.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>
</div>
