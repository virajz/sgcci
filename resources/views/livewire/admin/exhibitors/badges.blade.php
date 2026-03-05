<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.exhibitors.index')" wire:navigate>
            Back to Exhibitors
        </flux:button>
    </div>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $booking->brand_name }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Stalls: {{ implode(', ', $booking->selected_stalls) }} &middot;
                {{ $members->count() }} / {{ $booking->badge_limit }} badges used
            </flux:text>
        </div>

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

    {{-- QR info --}}
    <flux:card class="flex items-start gap-6">
        <div class="flex-shrink-0">
            {!! $qrSvg !!}
        </div>
        <div class="space-y-1">
            <flux:heading size="sm">Exhibitor QR Code</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                This QR code is printed on each badge. When scanned:
            </flux:text>
            <ul class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                <li>• <strong>Admin</strong> → this badges page</li>
                <li>• <strong>Exhibitor</strong> → their own badges page</li>
                <li>• <strong>Visitor / public</strong> → WhatsApp enquiry</li>
            </ul>
            <flux:text class="mt-2 font-mono text-xs text-zinc-400">
                {{ route('exhibitor.scan', $booking->booking_code) }}
            </flux:text>
        </div>
    </flux:card>

    {{-- Badge members grid --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Team Members</flux:heading>

        @if ($members->isEmpty())
            <div class="py-10 text-center">
                <flux:icon.users class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">No badge members added yet. Add up to
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
                                alt="Badge — {{ $member->name }}"
                                class="w-full h-auto"
                                loading="lazy">
                            <div
                                class="absolute inset-0 flex items-center justify-center transition-opacity opacity-0 bg-black/30 group-hover:opacity-100 rounded-t-xl">
                                <flux:icon.eye class="w-8 h-8 text-white" />
                            </div>
                        </button>

                        <div class="flex items-center justify-between gap-2 p-3">
                            <flux:text class="font-semibold truncate">{{ $member->name }}</flux:text>
                            <div class="flex flex-shrink-0 gap-1">
                                <flux:button size="sm" variant="ghost" icon="arrow-down-tray" iconVariant="outline"
                                    :href="route('exhibitor.badges.download', [$booking, $member])" />
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
                <flux:label>Photo <span class="text-zinc-400">(optional — leave blank to keep existing)</span></flux:label>
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
                        <flux:button variant="ghost" wire:click="$set('showBadgePreviewModal', false)">Close</flux:button>
                        <flux:button variant="primary" icon="arrow-down-tray"
                            :href="route('exhibitor.badges.download', [$booking, $previewMember])">
                            Download
                        </flux:button>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>
</div>
