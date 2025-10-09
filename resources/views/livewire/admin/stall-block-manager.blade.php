<div>
    {{-- Dropdown Button --}}
    <flux:dropdown>
        <flux:button icon="squares-2x2" iconVariant="outline">Manage Stalls</flux:button>

        <flux:menu class="min-w-48">
            <flux:menu.item icon="lock-closed" iconVariant="outline" wire:click="openBlockModal">Block Stalls
            </flux:menu.item>
            <flux:menu.item icon="lock-open" iconVariant="outline" wire:click="openReleaseModal">Release Stalls
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>

    {{-- Block Stalls Modal --}}
    <flux:modal wire:model="showBlockModal" variant="flyout">
        <flux:heading size="lg" class="mb-4">Block Stalls</flux:heading>

        <flux:subheading class="mb-6">
            Enter comma-separated stall numbers to block them manually. These stalls will be unavailable for booking.
        </flux:subheading>

        <flux:field>
            <flux:label>Stall Numbers</flux:label>
            <flux:textarea wire:model="stallNumbers" placeholder="e.g., 101, 102, 103" rows="4" />
            <flux:error name="stallNumbers" />
            <flux:description>Enter stall numbers separated by commas</flux:description>
        </flux:field>

        <div class="flex justify-end gap-3 mt-6">
            <flux:button variant="ghost" wire:click="$set('showBlockModal', false)">Cancel</flux:button>
            <flux:button variant="primary" icon="lock-closed" iconVariant="outline" wire:click="blockStalls">Block
                Stalls</flux:button>
        </div>
    </flux:modal>

    {{-- Release Stalls Modal --}}
    <flux:modal wire:model="showReleaseModal" variant="flyout">
        <flux:heading size="lg" class="mb-4">Release Blocked Stalls</flux:heading>

        <flux:subheading class="mb-6">
            Select stalls to release from manual block. Released stalls will become available for booking again.
        </flux:subheading>

        <div class="overflow-y-auto max-h-96">
            @if ($blockedStalls->count() > 0)
                <div class="space-y-3">
                    @foreach ($blockedStalls as $booking)
                        <div
                            class="flex items-center justify-between gap-4 p-4 transition-colors border rounded-lg dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach ($booking->selected_stalls as $stall)
                                        <flux:badge variant="outline" size="sm">{{ $stall }}</flux:badge>
                                    @endforeach
                                </div>
                                <flux:text size="sm" class="text-zinc-500">
                                    Blocked {{ $booking->blocked_at?->diffForHumans() }}
                                    @if ($booking->blockedBy)
                                        by {{ $booking->blockedBy->name }}
                                    @endif
                                </flux:text>
                            </div>
                            <flux:button size="sm" icon="lock-open" iconVariant="outline"
                                wire:click="confirmRelease({{ $booking->id }})">
                                Release
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <flux:icon.lock-open variant="outline" class="w-16 h-16 mx-auto mb-4 text-zinc-400" />
                    <flux:heading size="lg" class="mb-2">No Blocked Stalls</flux:heading>
                    <flux:text class="text-zinc-500">There are currently no manually blocked stalls.</flux:text>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 pt-4 mt-6 border-t dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('showReleaseModal', false)">Close</flux:button>
        </div>
    </flux:modal>

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
