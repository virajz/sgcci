<div class="space-y-4">
    {{-- Page header --}}
    <flux:card>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4 min-w-0">
                <div class="shrink-0">
                    {!! $qrSvg !!}
                </div>
                <div class="min-w-0 space-y-1 pt-1">
                    <flux:heading size="xl">{{ $booking->brand_name }}</flux:heading>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $booking->contact_person }}
                        </flux:text>
                        <flux:separator vertical class="h-4" />
                        <div class="flex flex-wrap gap-1">
                            @foreach ($booking->selected_stalls as $stall)
                                <flux:badge size="sm" color="zinc">{{ $stall }}</flux:badge>
                            @endforeach
                        </div>
                        <flux:separator vertical class="h-4" />
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $members->count() }} / {{ $booking->badge_limit }} badges used
                        </flux:text>
                    </div>
                    <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                        Visitors scan this QR to enquire via WhatsApp
                    </flux:text>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
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
    </flux:card>

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
            <div
                class="grid grid-cols-1 gap-6 lg:grid-cols-3"
                x-data="{
                    selectedMemberId: {{ $selectedMemberId ?? 'null' }},
                    deletingMemberId: null,
                    deletingMemberName: '',
                    editingMemberId: null,
                    editingMemberName: '',
                    editingMemberPhone: '',
                    openDeleteModal(id, name) {
                        this.deletingMemberId = id;
                        this.deletingMemberName = name;
                        $wire.set('deletingMemberId', id);
                        $wire.set('deletingMemberName', name);
                        $flux.modal('delete-member').show();
                    },
                    openEditModal(id, name, phone) {
                        this.editingMemberId = id;
                        this.editingMemberName = name;
                        this.editingMemberPhone = phone;
                        $wire.set('editingMemberId', id);
                        $wire.set('memberName', name);
                        $wire.set('memberPhoneNumber', phone);
                        $wire.set('memberPhoto', null);
                        $flux.modal('edit-member').show();
                    }
                }"
                x-on:member-deleted.window="selectedMemberId = $event.detail.selectedMemberId"
            >
                {{-- Left: member list --}}
                <div class="flex flex-col gap-2">
                    @foreach ($members as $member)
                        <button
                            type="button"
                            wire:key="member-{{ $member->id }}"
                            x-on:click="selectedMemberId = {{ $member->id }}"
                            x-bind:class="selectedMemberId === {{ $member->id }}
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40'
                                : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-3 w-full text-left rounded-xl border px-4 py-3 transition-colors"
                        >
                            @if ($member->photo)
                                <img src="{{ route('exhibitor.badges.photo', [$booking, $member]) }}"
                                    alt="{{ $member->name }}"
                                    class="w-10 h-10 rounded-full object-cover shrink-0">
                            @else
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 shrink-0">
                                    <flux:icon.user class="w-5 h-5 text-zinc-400 dark:text-zinc-500" />
                                </div>
                            @endif
                            <div class="min-w-0">
                                <flux:text class="font-semibold truncate">{{ $member->name }}</flux:text>
                                @if ($member->phone_number)
                                    <flux:text class="text-xs truncate text-zinc-500 dark:text-zinc-400">{{ $member->phone_number }}</flux:text>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Right: badge previews (one per member, shown/hidden via Alpine) --}}
                <div class="lg:col-span-2">
                    @foreach ($members as $member)
                        <div class="relative" x-show="selectedMemberId === {{ $member->id }}" x-cloak>
                            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <img src="{{ route('exhibitor.badges.inline', [$booking, $member]) }}"
                                    alt="Badge — {{ $member->name }}"
                                    class="w-full h-auto"
                                    loading="lazy">
                            </div>

                            <div class="absolute bottom-3 right-3 flex items-center gap-1 rounded-lg bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm px-1.5 py-1 shadow">
                                <flux:tooltip content="Download">
                                    <flux:button variant="ghost" size="sm" icon="arrow-down-tray" iconVariant="outline"
                                        :href="route('exhibitor.badges.download', [$booking, $member])" />
                                </flux:tooltip>
                                <flux:tooltip content="Edit">
                                    <flux:button variant="ghost" size="sm" icon="pencil" iconVariant="outline"
                                        x-on:click="openEditModal({{ $member->id }}, @js($member->name), @js($member->phone_number ?? ''))" />
                                </flux:tooltip>
                                <flux:tooltip content="Delete">
                                    <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                        class="text-red-500 hover:text-red-600"
                                        x-on:click="openDeleteModal({{ $member->id }}, @js($member->name))" />
                                </flux:tooltip>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-member" class="w-full max-w-sm">
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
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteMember" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteMember">Remove</span>
                    <span wire:loading wire:target="deleteMember">Removing...</span>
                </flux:button>
            </div>
        </div>
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
                <flux:label>Phone Number</flux:label>
                <flux:input wire:model="memberPhoneNumber" placeholder="+91 98765 43210" />
                <flux:error name="memberPhoneNumber" />
            </flux:field>

            <flux:file-upload wire:model="memberPhoto" label="Photo" description="Max 2MB. JPG, PNG, or WEBP." :error="$errors->first('memberPhoto')">
                <flux:file-upload.dropzone heading="Drop photo here or click to browse" text="JPG, PNG, or WEBP, up to 2MB" with-progress inline />
            </flux:file-upload>

            @if ($memberPhoto)
                <flux:file-item
                    :heading="$memberPhoto->getClientOriginalName()"
                    :image="$memberPhoto->temporaryUrl()"
                    :size="$memberPhoto->getSize()"
                >
                    <x-slot name="actions">
                        <flux:file-item.remove wire:click="$set('memberPhoto', null)" aria-label="Remove photo" />
                    </x-slot>
                </flux:file-item>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="addMember" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="addMember">Add Member</span>
                    <span wire:loading wire:target="addMember">Adding...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Member Modal --}}
    <flux:modal name="edit-member" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Edit Badge Member</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="memberName" placeholder="Full name" />
                <flux:error name="memberName" />
            </flux:field>

            <flux:field>
                <flux:label>Phone Number</flux:label>
                <flux:input wire:model="memberPhoneNumber" placeholder="+91 98765 43210" />
                <flux:error name="memberPhoneNumber" />
            </flux:field>

            <flux:file-upload wire:model="memberPhoto" label="Photo" description="Optional — leave blank to keep existing. Max 2MB. JPG, PNG, or WEBP." :error="$errors->first('memberPhoto')">
                <flux:file-upload.dropzone heading="Drop photo here or click to browse" text="JPG, PNG, or WEBP, up to 2MB" with-progress inline />
            </flux:file-upload>

            @if ($memberPhoto)
                <flux:file-item
                    :heading="$memberPhoto->getClientOriginalName()"
                    :image="$memberPhoto->temporaryUrl()"
                    :size="$memberPhoto->getSize()"
                >
                    <x-slot name="actions">
                        <flux:file-item.remove wire:click="$set('memberPhoto', null)" aria-label="Remove photo" />
                    </x-slot>
                </flux:file-item>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="updateMember" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateMember">Save Changes</span>
                    <span wire:loading wire:target="updateMember">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
