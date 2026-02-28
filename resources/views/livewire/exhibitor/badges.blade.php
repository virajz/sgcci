<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Badges</flux:heading>
        <div class="flex items-center gap-2">
            @if ($members->isNotEmpty())
                <flux:button variant="ghost" icon="arrow-down-tray"
                    :href="route('exhibitor.badges.download-all', $booking)">
                    Download All
                </flux:button>
            @endif
            @if ($members->count() < $booking->badge_limit)
                <flux:button variant="primary" icon="plus" wire:click="openAddModal">Add Member</flux:button>
            @endif
        </div>
    </div>

    {{-- Exhibitor Info + QR --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <flux:card class="h-full">
                <flux:heading size="lg" class="mb-4">Company Details</flux:heading>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-xs font-medium tracking-wide uppercase text-zinc-500 dark:text-zinc-400">
                            Company Name</flux:text>
                        <flux:text class="mt-1 font-semibold">{{ $booking->brand_name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium tracking-wide uppercase text-zinc-500 dark:text-zinc-400">
                            Stall No(s)</flux:text>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach ($booking->selected_stalls as $stall)
                                <flux:badge size="sm" color="zinc">{{ $stall }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium tracking-wide uppercase text-zinc-500 dark:text-zinc-400">
                            Contact Person</flux:text>
                        <flux:text class="mt-1">{{ $booking->contact_person }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium tracking-wide uppercase text-zinc-500 dark:text-zinc-400">
                            Badge Slots Used</flux:text>
                        <flux:text class="mt-1">{{ $members->count() }} / {{ $booking->badge_limit }}</flux:text>
                    </div>
                </dl>
            </flux:card>
        </div>

        {{-- QR Code --}}
        <flux:card class="flex flex-col items-center justify-center text-center">
            <flux:heading size="sm" class="mb-3">Exhibitor QR Code</flux:heading>
            <div class="inline-block overflow-hidden rounded-xl">
                {!! $qrSvg !!}
            </div>
            <flux:text class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                Printed on every badge — visitors scan to enquire via WhatsApp
            </flux:text>
        </flux:card>
    </div>

    {{-- Badge Members --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Team Members</flux:heading>

        @if ($members->isEmpty())
            <div class="py-10 text-center">
                <flux:icon.users class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">No members added yet. Add up to
                    {{ $booking->badge_limit }} members.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($members as $member)
                    <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700"
                        wire:key="member-{{ $member->id }}">
                        {{-- Clickable badge preview — opens modal --}}
                        <button type="button" wire:click="openBadgePreview({{ $member->id }})"
                            class="relative block w-full transition-opacity cursor-pointer bg-zinc-50 dark:bg-zinc-800 hover:opacity-90 group">
                            <img src="{{ route('exhibitor.badges.inline', [$booking, $member]) }}"
                                alt="Badge — {{ $member->name }}" class="w-full h-auto" loading="lazy">
                            <div
                                class="absolute inset-0 flex items-center justify-center transition-opacity opacity-0 bg-black/30 group-hover:opacity-100 rounded-t-xl">
                                <flux:icon.eye class="w-8 h-8 text-white" />
                            </div>
                        </button>

                        <div class="flex items-center justify-between gap-2 p-3">
                            <flux:text class="font-semibold truncate">{{ $member->name }}</flux:text>
                            <div class="flex flex-shrink-0 gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil" iconVariant="outline"
                                    wire:click="openEditModal({{ $member->id }})" />
                                <flux:button size="sm" variant="ghost" icon="trash" iconVariant="outline"
                                    class="text-red-500 hover:text-red-600"
                                    wire:click="confirmDelete({{ $member->id }})" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" name="delete-member" class="w-full max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Remove member?</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                    @if ($deletingMemberName)
                        <strong>{{ $deletingMemberName }}</strong> will be removed from badges. This cannot be undone.
                    @endif
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteMember" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteMember">Remove</span>
                    <span wire:loading wire:target="deleteMember">Removing...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Badge Preview Modal --}}
    <flux:modal wire:model="showBadgePreviewModal" name="badge-preview" class="w-full max-w-4xl">
        @if ($previewingMemberId)
            @php $previewMember = $members->firstWhere('id', $previewingMemberId); @endphp
            @if ($previewMember)
                <div class="space-y-4">
                    <flux:heading size="lg">{{ $previewMember->name }}</flux:heading>
                    <img src="{{ route('exhibitor.badges.inline', [$booking, $previewMember]) }}"
                        alt="Badge — {{ $previewMember->name }}" class="w-full h-auto rounded-lg">
                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost" wire:click="$set('showBadgePreviewModal', false)">Close
                        </flux:button>
                        <flux:button variant="primary" icon="arrow-down-tray"
                            :href="route('exhibitor.badges.download', [$booking, $previewMember])">
                            Download
                        </flux:button>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>

    {{-- Add Member Modal --}}
    <flux:modal wire:model="showAddModal" name="add-member" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Add Badge Member</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="memberName" placeholder="Full name" />
                <flux:error name="memberName" />
            </flux:field>

            <flux:field>
                <flux:label>Photo <span class="text-zinc-400">(optional)</span></flux:label>
                <flux:input type="file" wire:model="memberPhoto" accept="image/*" />
                <flux:description>Max 2MB. JPG, PNG, or WEBP.</flux:description>
                <flux:error name="memberPhoto" />
            </flux:field>

            @if ($memberPhoto)
                <div>
                    <flux:text class="mb-1 text-xs text-zinc-500">Preview</flux:text>
                    <img src="{{ $memberPhoto->temporaryUrl() }}" class="object-cover w-16 h-16 rounded-full">
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="addMember" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="addMember">Add Member</span>
                    <span wire:loading wire:target="addMember">Adding...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Member Modal --}}
    <flux:modal wire:model="showEditModal" name="edit-member" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Edit Badge Member</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="memberName" placeholder="Full name" />
                <flux:error name="memberName" />
            </flux:field>

            <flux:field>
                <flux:label>Photo <span class="text-zinc-400">(optional — leave blank to keep existing)</span>
                </flux:label>
                <flux:input type="file" wire:model="memberPhoto" accept="image/*" />
                <flux:description>Max 2MB. JPG, PNG, or WEBP.</flux:description>
                <flux:error name="memberPhoto" />
            </flux:field>

            @if ($memberPhoto)
                <div>
                    <flux:text class="mb-1 text-xs text-zinc-500">New photo preview</flux:text>
                    <img src="{{ $memberPhoto->temporaryUrl() }}" class="object-cover w-16 h-16 rounded-full">
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="updateMember" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateMember">Save Changes</span>
                    <span wire:loading wire:target="updateMember">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
