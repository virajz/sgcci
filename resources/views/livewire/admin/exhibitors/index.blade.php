<div
    class="space-y-6"
    x-data="{
        smsBookingId: null,
        smsBookingName: '',
        columns: (function() {
            const storageKey = 'admin.exhibitors.visible_columns';
            const defaults = @js($defaultColumns);
            try {
                const stored = JSON.parse(localStorage.getItem(storageKey) || 'null');
                if (stored && typeof stored === 'object') {
                    return Object.assign({}, defaults, stored);
                }
            } catch (e) {}
            return defaults;
        })(),
        saveColumns() {
            try {
                localStorage.setItem('admin.exhibitors.visible_columns', JSON.stringify(this.columns));
            } catch (e) {}
        },
        resetToDefaults(cols) {
            this.columns = cols;
            try {
                localStorage.removeItem('admin.exhibitors.visible_columns');
            } catch (e) {}
        }
    }"
    x-on:columns-reset.window="resetToDefaults($event.detail.columns)"
>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Exhibitors</flux:heading>
        <div class="flex items-center gap-2">
            @if (count($selectedBookings) > 0)
                <flux:button variant="primary" icon="key" iconVariant="outline" wire:click="openBulkConfirmModal"
                    :loading="false">
                    Generate Credentials ({{ count($selectedBookings) }})
                </flux:button>
                <flux:button variant="filled" icon="chat-bubble-left-ellipsis" iconVariant="outline"
                    wire:click="openBulkSmsConfirmModal" :loading="false">
                    Send SMS ({{ count($selectedBookings) }})
                </flux:button>
            @endif

            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" icon="view-columns" iconVariant="outline">Columns</flux:button>
                <flux:menu class="min-w-48">
                    @foreach ($defaultColumns as $column => $isVisible)
                        <flux:menu.checkbox
                            :checked="$isVisible"
                            keep-open
                            x-on:change="columns['{{ $column }}'] = $event.target.value; saveColumns()"
                        >
                            {{ $this->getColumnLabel($column) }}
                        </flux:menu.checkbox>
                    @endforeach
                    <flux:menu.separator />
                    <flux:menu.item wire:click="resetColumns" icon="arrow-path" iconVariant="outline">
                        Reset to Default
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <flux:button variant="filled" icon="arrow-down-tray" iconVariant="outline"
                :href="route('admin.exhibitors.download-svgs')">Download SVGs</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openAddExhibitorModal" :loading="false">Add
                Exhibitor</flux:button>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Search by code, brand, contact, phone, or email..." icon="magnifying-glass"
            iconVariant="outline" class="max-w-md" />
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
                            <flux:table.column x-show="columns['booking_code']">Booking Code</flux:table.column>
                            <flux:table.column x-show="columns['brand_contact']">Brand / Contact</flux:table.column>
                            <flux:table.column x-show="columns['email']">Email</flux:table.column>
                            <flux:table.column x-show="columns['phone']">Phone</flux:table.column>
                            <flux:table.column x-show="columns['exhibition']">Exhibition</flux:table.column>
                            <flux:table.column x-show="columns['stalls']">Stalls</flux:table.column>
                            <flux:table.column x-show="columns['badges']">Badges</flux:table.column>
                            <flux:table.column x-show="columns['invites']">Invites</flux:table.column>
                            <flux:table.column x-show="columns['login_password']">Login Password</flux:table.column>
                            <flux:table.column x-show="columns['paid_at']">Paid At</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($bookings as $booking)
                                <flux:table.row :key="$booking->id">
                                    <flux:table.cell>
                                        <flux:checkbox :value="$booking->id" />
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['booking_code']">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.inquiries.show', $booking) }}"
                                                class="font-mono text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                {{ $booking->booking_code }}
                                            </a>
                                            @if ($booking->status === \App\BookingStatus::PaymentPending)
                                                <flux:badge size="sm" color="orange" variant="pill">Part Paid</flux:badge>
                                            @endif
                                            @if ($booking->is_manually_added)
                                                <flux:badge size="sm" color="blue" variant="pill">Manual</flux:badge>
                                            @endif
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['brand_contact']">
                                        <div class="font-medium">{{ $booking->brand_name }}</div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $booking->contact_person }}</div>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['email']">
                                        <flux:text class="text-sm">{{ $booking->email }}</flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['phone']">
                                        <flux:text class="text-sm">{{ $booking->phone_code }} {{ $booking->phone_number }}</flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['exhibition']">
                                        <flux:text class="text-sm">{{ $booking->exhibition?->title ?? '—' }}</flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['stalls']">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($booking->selected_stalls as $stall)
                                                <flux:badge size="sm" color="zinc">{{ $stall }}</flux:badge>
                                            @endforeach
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['badges']">
                                        @if ($editingBadgeLimitId === $booking->id)
                                            <div class="flex items-center gap-2">
                                                <flux:input type="number" wire:model="editingBadgeLimit"
                                                    min="1" max="100" class="w-20" size="sm" />
                                                <flux:button size="sm" variant="primary"
                                                    wire:click="saveBadgeLimit" icon="check" />
                                                <flux:button size="sm" variant="ghost"
                                                    wire:click="cancelEditingBadgeLimit" icon="x-mark" />
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <flux:text class="text-sm">
                                                    {{ $booking->badgeMembers->count() }} / {{ $booking->badge_limit }}
                                                </flux:text>
                                                <flux:tooltip content="Edit badge limit">
                                                    <flux:button size="sm" variant="ghost" icon="pencil"
                                                        iconVariant="outline"
                                                        wire:click="startEditingBadgeLimit({{ $booking->id }}, {{ $booking->badge_limit }})"
                                                        :loading="false" />
                                                </flux:tooltip>
                                            </div>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['invites']">
                                        @if ($editingInvitedGuestsLimitId === $booking->id)
                                            <div class="flex items-center gap-2">
                                                <flux:input type="number" wire:model="editingInvitedGuestsLimit"
                                                    min="1" max="500" class="w-20" size="sm" />
                                                <flux:button size="sm" variant="primary"
                                                    wire:click="saveInvitedGuestsLimit" icon="check" />
                                                <flux:button size="sm" variant="ghost"
                                                    wire:click="cancelEditingInvitedGuestsLimit" icon="x-mark" />
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <flux:text class="text-sm">
                                                    {{ $booking->invitedGuests->count() }} / {{ $booking->invited_guests_limit }}
                                                </flux:text>
                                                <flux:tooltip content="Edit invite limit">
                                                    <flux:button size="sm" variant="ghost" icon="pencil"
                                                        iconVariant="outline"
                                                        wire:click="startEditingInvitedGuestsLimit({{ $booking->id }}, {{ $booking->invited_guests_limit }})"
                                                        :loading="false" />
                                                </flux:tooltip>
                                            </div>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['login_password']">
                                        @if ($booking->login_password)
                                            <div class="flex items-center gap-2" x-data>
                                                <flux:text class="font-mono text-sm">{{ $booking->login_password }}</flux:text>
                                                <flux:tooltip content="Copy login details">
                                                    <flux:button size="sm" variant="ghost"
                                                        icon="clipboard-document" iconVariant="outline"
                                                        x-on:click="navigator.clipboard.writeText(
                                                            'Login URL: {{ route('login') }}\nEmail: {{ $booking->email }}\nPhone: {{ $booking->phone_number }}\nPassword: {{ $booking->login_password }}'
                                                        ).then(() => {
                                                            $flux.toast({ variant: 'success', heading: 'Copied!', text: 'Login details copied to clipboard' });
                                                        })">
                                                    </flux:button>
                                                </flux:tooltip>
                                            </div>
                                        @else
                                            <flux:tooltip content="Generate login credentials">
                                                <flux:button size="sm" variant="ghost" icon="key"
                                                    iconVariant="outline"
                                                    wire:click="openGenerateConfirmModal({{ $booking->id }}, '{{ addslashes($booking->brand_name) }}')"
                                                    :loading="false" />
                                            </flux:tooltip>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns['paid_at']">
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $booking->payment_completed_at?->format('M d, Y') ?? '—' }}
                                        </flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center gap-1">
                                            @if ($booking->login_password)
                                                <flux:tooltip content="Send credentials SMS">
                                                    <flux:button size="sm" variant="ghost"
                                                        icon="chat-bubble-left-ellipsis" iconVariant="outline"
                                                        x-on:click="smsBookingId = {{ $booking->id }}; smsBookingName = '{{ addslashes($booking->brand_name) }}'; $flux.modal('send-sms-confirm').show()" />
                                                </flux:tooltip>
                                            @endif
                                            <flux:tooltip content="View badges">
                                                <flux:button size="sm" variant="ghost" icon="identification"
                                                    iconVariant="outline"
                                                    x-on:click="window.location.href = '{{ route('admin.exhibitors.badges', $booking) }}'" />
                                            </flux:tooltip>
                                            <flux:tooltip content="View invited guests">
                                                <flux:button size="sm" variant="ghost" icon="user-plus"
                                                    iconVariant="outline"
                                                    x-on:click="window.location.href = '{{ route('admin.exhibitors.invited-guests', $booking) }}'" />
                                            </flux:tooltip>
                                            <flux:tooltip content="Download QR SVG">
                                                <flux:button size="sm" variant="ghost" icon="qr-code"
                                                    iconVariant="outline"
                                                    x-on:click="window.location.href = '{{ route('admin.exhibitors.download-svg', $booking) }}'" />
                                            </flux:tooltip>
                                            @if ($booking->is_manually_added)
                                                <flux:tooltip content="Delete exhibitor">
                                                    <flux:button size="sm" variant="ghost" icon="trash"
                                                        iconVariant="outline" class="text-red-500 hover:text-red-600"
                                                        wire:click="confirmDeleteExhibitor({{ $booking->id }}, '{{ addslashes($booking->brand_name) }}')"
                                                        :loading="false" />
                                                </flux:tooltip>
                                            @endif
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
                        Exhibitors appear here once their payment is completed or they have been moved to exhibitor
                        status.
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
                <flux:button wire:click="generateCredentials" variant="primary" icon="key"
                    iconVariant="outline">
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
                    Generate login credentials for <strong>{{ count($selectedBookings) }}</strong> selected
                    exhibitor(s)?
                    This will create user accounts, generate passwords, and send an SMS to each.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="bulkGenerateCredentials" variant="primary" icon="key"
                    iconVariant="outline">
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
                <flux:button
                    x-on:click="$wire.sendCredentialsSms(smsBookingId); $flux.modal('send-sms-confirm').close()"
                    variant="primary" icon="chat-bubble-left-ellipsis" iconVariant="outline">
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
                    Send login credentials via SMS to <strong>{{ count($selectedBookings) }}</strong> selected
                    exhibitor(s)?
                    Only exhibitors with generated credentials will receive an SMS.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="bulkSendCredentialsSms" variant="primary" icon="chat-bubble-left-ellipsis"
                    iconVariant="outline">
                    Send SMS
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Exhibitor Modal --}}
    <flux:modal wire:model="showDeleteExhibitorModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Exhibitor?</flux:heading>
                <flux:text class="mt-2">
                    Permanently delete <strong>{{ $deleteExhibitorName }}</strong>? This will remove the booking and
                    their user account. This cannot be undone.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="deleteExhibitor" variant="danger" icon="trash" iconVariant="outline">
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Add Exhibitor Modal --}}
    <flux:modal wire:model="showAddExhibitorModal" class="w-full max-w-lg">
        <div class="space-y-5">
            <flux:heading size="lg">Add Exhibitor Manually</flux:heading>

            <flux:field>
                <flux:label>Exhibition</flux:label>
                <flux:select wire:model="addExhibitorExhibitionId">
                    @foreach ($exhibitions as $exhibition)
                        <flux:select.option :value="$exhibition->id">{{ $exhibition->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="addExhibitorExhibitionId" />
            </flux:field>

            <flux:field>
                <flux:label>Brand / Company Name</flux:label>
                <flux:input wire:model="addExhibitorBrandName" placeholder="e.g. Acme Pvt. Ltd." />
                <flux:error name="addExhibitorBrandName" />
            </flux:field>

            <flux:field>
                <flux:label>Contact Person</flux:label>
                <flux:input wire:model="addExhibitorContactPerson" placeholder="Full name" />
                <flux:error name="addExhibitorContactPerson" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="addExhibitorEmail" placeholder="exhibitor@example.com" />
                <flux:error name="addExhibitorEmail" />
            </flux:field>

            <div class="grid grid-cols-3 gap-3">
                <flux:field>
                    <flux:label>Code</flux:label>
                    <flux:input wire:model="addExhibitorPhoneCode" placeholder="+91" />
                    <flux:error name="addExhibitorPhoneCode" />
                </flux:field>
                <flux:field class="col-span-2">
                    <flux:label>Phone Number</flux:label>
                    <flux:input wire:model="addExhibitorPhoneNumber" placeholder="9876543210" />
                    <flux:error name="addExhibitorPhoneNumber" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Stall Numbers <span class="text-zinc-400">(optional, comma-separated)</span></flux:label>
                <flux:input wire:model="addExhibitorStalls" placeholder="e.g. 12, 13, 45" />
                <flux:description>Enter stall numbers separated by commas.</flux:description>
                <flux:error name="addExhibitorStalls" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" icon="user-plus" wire:click="addExhibitor"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="addExhibitor">Add Exhibitor</span>
                    <span wire:loading wire:target="addExhibitor">Adding...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
