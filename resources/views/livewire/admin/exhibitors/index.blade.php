<div class="space-y-6" x-data="{ smsBookingId: null, smsBookingName: '' }">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Exhibitors</flux:heading>
    </div>

    <div class="flex items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Search by code, brand, contact, phone, or email..." icon="magnifying-glass"
            iconVariant="outline" class="max-w-md" />

        @if (count($selectedBookings) > 0)
            <flux:button variant="primary" icon="key" iconVariant="outline"
                wire:click="openBulkConfirmModal">
                Generate Credentials ({{ count($selectedBookings) }})
            </flux:button>
            <flux:button variant="filled" icon="chat-bubble-left-ellipsis" iconVariant="outline"
                wire:click="openBulkSmsConfirmModal">
                Send SMS ({{ count($selectedBookings) }})
            </flux:button>
        @endif
    </div>

    <flux:card class="overflow-hidden">
        @if ($bookings->count() > 0)
            <div class="overflow-x-auto">
                <flux:checkbox.group wire:model.live="selectedBookings">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="w-10">
                                <flux:checkbox.all />
                            </flux:table.column>
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
                                <flux:table.row :key="$booking->id">
                                    <flux:table.cell>
                                        <flux:checkbox :value="$booking->id" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.inquiries.show', $booking) }}"
                                                class="font-mono text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                                                wire:navigate>
                                                {{ $booking->booking_code }}
                                            </a>
                                            @if ($booking->status === \App\BookingStatus::PaymentPending)
                                                <flux:badge size="sm" color="orange" variant="pill">Part Paid</flux:badge>
                                            @endif
                                        </div>
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
                                            <flux:button size="sm" variant="ghost" icon="key" iconVariant="outline"
                                                wire:click="openGenerateConfirmModal({{ $booking->id }}, '{{ addslashes($booking->brand_name) }}')">
                                                Generate
                                            </flux:button>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $booking->payment_completed_at?->format('M d, Y') ?? '—' }}
                                        </flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            @if ($booking->login_password)
                                                <flux:button size="sm" variant="ghost" icon="chat-bubble-left-ellipsis" iconVariant="outline"
                                                    x-on:click="smsBookingId = {{ $booking->id }}; smsBookingName = '{{ addslashes($booking->brand_name) }}'; $flux.modal('send-sms-confirm').show()">
                                                    Send SMS
                                                </flux:button>
                                            @endif
                                            <flux:button size="sm" variant="ghost" icon="identification" iconVariant="outline"
                                                :href="route('admin.exhibitors.badges', $booking)" wire:navigate>
                                                Badges
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:checkbox.group>
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
                        Exhibitors appear here once their payment is completed or they have been moved to exhibitor status.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Single Generate Credentials Modal --}}
    <flux:modal wire:model="showGenerateConfirmModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Generate Credentials?</flux:heading>
                <flux:text class="mt-2">
                    Generate login credentials for <strong>{{ $confirmGenerateName }}</strong>?
                    A user account will be created, a password generated, and an SMS will be sent.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="generateCredentials" variant="primary" icon="key" iconVariant="outline">
                    Generate
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk Generate Confirmation Modal --}}
    <flux:modal wire:model="showBulkConfirmModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Generate Credentials?</flux:heading>
                <flux:text class="mt-2">
                    Generate login credentials for <strong>{{ count($selectedBookings) }}</strong> selected exhibitor(s)?
                    This will create user accounts, generate passwords, and send an SMS to each.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="bulkGenerateCredentials" variant="primary" icon="key" iconVariant="outline">
                    Generate
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Single Send SMS Modal --}}
    <flux:modal name="send-sms-confirm" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Send SMS?</flux:heading>
                <flux:text class="mt-2">
                    Resend login credentials via SMS to <strong x-text="smsBookingName"></strong>?
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button x-on:click="$wire.sendCredentialsSms(smsBookingId); $flux.modal('send-sms-confirm').close()" variant="primary" icon="chat-bubble-left-ellipsis" iconVariant="outline">
                    Send SMS
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk Send SMS Modal --}}
    <flux:modal wire:model="showBulkSmsConfirmModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Send SMS?</flux:heading>
                <flux:text class="mt-2">
                    Send login credentials via SMS to <strong>{{ count($selectedBookings) }}</strong> selected exhibitor(s)?
                    Only exhibitors with generated credentials will receive an SMS.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="bulkSendCredentialsSms" variant="primary" icon="chat-bubble-left-ellipsis" iconVariant="outline">
                    Send SMS
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
