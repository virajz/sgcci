<div x-data="{
    deletingGuestId: null,
    deletingGuestName: '',
    openDeleteModal(id, name) {
        this.deletingGuestId = id;
        this.deletingGuestName = name;
        $wire.set('deletingGuestId', id);
        $wire.set('deletingGuestName', name);
        $wire.set('showDeleteModal', true);
    }
}"
class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Invited Guests</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                Invite visitors to the exhibition on your behalf. They will receive a WhatsApp confirmation with their
                pass.
            </flux:text>
        </div>
        @if ($guestCount < $booking->invited_guests_limit)
            <flux:button variant="primary" icon="plus" wire:click="openAddModal">Add Guest</flux:button>
        @endif
    </div>

    <flux:card class="space-y-4">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <flux:text class="text-sm font-medium">
                        {{ $guestCount }} / {{ $booking->invited_guests_limit }} guests added
                    </flux:text>
                    @if ($guestCount >= $booking->invited_guests_limit)
                        <flux:badge size="sm" color="red">Limit Reached</flux:badge>
                    @endif
                </div>
                <div class="w-full h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $guestCount >= $booking->invited_guests_limit ? 'bg-red-500' : 'bg-blue-500' }}"
                        style="width: {{ $booking->invited_guests_limit > 0 ? min(100, round(($guestCount / $booking->invited_guests_limit) * 100)) : 0 }}%">
                    </div>
                </div>
            </div>
        </div>

        @if ($guests->isEmpty())
            <div class="py-12 text-center">
                <flux:icon.user-plus class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mb-1">No invited guests yet</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400 mb-4">
                    Add guests to invite them to the exhibition.
                </flux:text>
                <flux:button variant="primary" icon="plus" wire:click="openAddModal">Add First Guest</flux:button>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Phone</flux:table.column>
                    <flux:table.column>Company</flux:table.column>
                    <flux:table.column>Location</flux:table.column>
                    <flux:table.column>Pass Code</flux:table.column>
                    <flux:table.column>Added</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($guests as $guest)
                        <flux:table.row wire:key="guest-{{ $guest->id }}">
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $guest->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $guest->phone_number }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $guest->company_name ?? '—' }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $guest->city }}, {{ $guest->state }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc" class="font-mono">
                                    {{ $guest->registration_code }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $guest->created_at->format('M d, Y') }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:tooltip content="Remove guest">
                                    <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                        class="text-red-500 hover:text-red-600"
                                        x-on:click="openDeleteModal({{ $guest->id }}, @js($guest->name))" />
                                </flux:tooltip>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="w-full max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Remove guest?</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                    <strong x-text="deletingGuestName"></strong> will be removed from your invited guests. This cannot
                    be undone.
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteGuest" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteGuest">Remove</span>
                    <span wire:loading wire:target="deleteGuest">Removing...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Add Guest Modal --}}
    <flux:modal wire:model="showAddModal" name="add-guest" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Add Invited Guest</flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Phone Number <flux:badge size="sm" color="red" class="ms-1">Required
                        </flux:badge>
                    </flux:label>
                    <flux:input wire:model="phoneNumber" placeholder="9876543210" />
                    <flux:error name="phoneNumber" />
                </flux:field>

                <flux:field>
                    <flux:label>Full Name <flux:badge size="sm" color="red" class="ms-1">Required</flux:badge>
                    </flux:label>
                    <flux:input wire:model="name" placeholder="Guest's full name" />
                    <flux:error name="name" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>State <flux:badge size="sm" color="red" class="ms-1">Required</flux:badge>
                    </flux:label>
                    <flux:select wire:model.live="state" searchable placeholder="Select state...">
                        @foreach ($states as $stateName)
                            <flux:select.option :value="$stateName">{{ $stateName }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="state" />
                </flux:field>

                <flux:field>
                    <flux:label>City <flux:badge size="sm" color="red" class="ms-1">Required</flux:badge>
                    </flux:label>
                    <flux:select wire:model="city" searchable placeholder="Select city..." :disabled="empty($state)">
                        @foreach ($this->cities as $cityName)
                            <flux:select.option :value="$cityName">{{ $cityName }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="city" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Company Name</flux:label>
                    <flux:input wire:model="companyName" placeholder="Company or organisation" />
                    <flux:error name="companyName" />
                </flux:field>

                <flux:field>
                    <flux:label>Designation</flux:label>
                    <flux:input wire:model="designation" placeholder="Job title or role" />
                    <flux:error name="designation" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="guest@example.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Business Segment</flux:label>
                    <flux:select wire:model="segment" placeholder="Select segment...">
                        <flux:select.option value="">— None —</flux:select.option>
                        <flux:select.option value="Business">Business</flux:select.option>
                        <flux:select.option value="Job (Working Professional)">Job (Working Professional)
                        </flux:select.option>
                        <flux:select.option value="Student">Student</flux:select.option>
                        <flux:select.option value="Housewife">Housewife</flux:select.option>
                        <flux:select.option value="Other">Other</flux:select.option>
                    </flux:select>
                    <flux:error name="segment" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="addGuest" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="addGuest">Add Guest</span>
                    <span wire:loading wire:target="addGuest">Adding...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
