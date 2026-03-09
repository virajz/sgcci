<div
    x-data="{
        deletingGuestId: null,
        deletingGuestName: '',
        openDeleteModal(id, name) {
            this.deletingGuestId = id;
            this.deletingGuestName = name;
            $wire.set('deletingGuestId', id);
            $wire.set('deletingGuestName', name);
            $flux.modal('delete-guest').show();
        }
    }"
    class="space-y-6"
>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Invited Guests — {{ $booking->brand_name }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ $guestCount }} / {{ $booking->invited_guests_limit }} guests added
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.exhibitors.index')" wire:navigate>
            Back to Exhibitors
        </flux:button>
    </div>

    <flux:card class="overflow-hidden">
        @if ($guests->isEmpty())
            <div class="py-12 text-center">
                <flux:icon.user-plus class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mb-1">No invited guests</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    This exhibitor has not added any invited guests yet.
                </flux:text>
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
                                <flux:badge size="sm" color="zinc" class="font-mono">{{ $guest->registration_code }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $guest->created_at->format('M d, Y') }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:tooltip content="Remove guest">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        iconVariant="outline"
                                        class="text-red-500 hover:text-red-600"
                                        x-on:click="openDeleteModal({{ $guest->id }}, @js($guest->name))"
                                    />
                                </flux:tooltip>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-guest" class="w-full max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Remove guest?</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                    <strong x-text="deletingGuestName"></strong> will be removed from invited guests. This cannot be undone.
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
</div>
