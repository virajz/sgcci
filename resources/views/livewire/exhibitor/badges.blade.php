<div
    x-data="{
        selectedMemberId: {{ $selectedMemberId ?? 'null' }},
        deletingMemberId: null,
        deletingMemberName: '',
        openDeleteModal(id, name) {
            this.deletingMemberId = id;
            this.deletingMemberName = name;
            $wire.set('deletingMemberId', id);
            $wire.set('deletingMemberName', name);
            $flux.modal('delete-member').show();
        },
        openEditModal(id, name, phone) {
            $wire.set('editingMemberId', id);
            $wire.set('memberName', name);
            $wire.set('memberPhoneNumber', phone);
            $wire.set('memberPhoto', null);
            $flux.modal('edit-member').show();
        }
    }"
    x-on:member-deleted.window="selectedMemberId = $event.detail.selectedMemberId"
    class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-start"
>
    {{-- Col 1: Member list --}}
    <flux:card class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <flux:heading size="lg">Team Members</flux:heading>
                <div class="flex items-center gap-2">
                    <div class="w-24 h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all {{ $members->count() >= $booking->badge_limit ? 'bg-red-500' : 'bg-blue-500' }}"
                            style="width: {{ $booking->badge_limit > 0 ? min(100, round($members->count() / $booking->badge_limit * 100)) : 0 }}%"
                        ></div>
                    </div>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $members->count() }} / {{ $booking->badge_limit }}
                    </flux:text>
                </div>
            </div>
            @if ($members->count() < $booking->badge_limit)
                <flux:button variant="primary" size="sm" icon="plus" wire:click="openAddModal">Add</flux:button>
            @endif
        </div>

        @if ($members->isEmpty())
            <div class="py-8 text-center">
                <flux:icon.users class="w-8 h-8 mx-auto mb-2 text-zinc-300 dark:text-zinc-600" />
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No members yet.</flux:text>
                <div class="mt-3">
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="openAddModal">Add First Member</flux:button>
                </div>
            </div>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($members as $member)
                    <div
                        wire:key="member-{{ $member->id }}"
                        x-bind:class="selectedMemberId === {{ $member->id }}
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40'
                            : 'border-zinc-200 dark:border-zinc-700'"
                        class="flex items-center gap-3 w-full rounded-xl border px-3 py-2.5 transition-colors"
                    >
                        <button
                            type="button"
                            x-on:click="selectedMemberId = {{ $member->id }}"
                            class="flex items-center gap-3 flex-1 min-w-0 text-left"
                        >
                            @if ($member->photo)
                                <img src="{{ route('exhibitor.badges.photo', [$booking, $member]) }}"
                                    alt="{{ $member->name }}"
                                    class="w-9 h-9 rounded-full object-cover shrink-0">
                            @else
                                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-zinc-200 dark:bg-zinc-700 shrink-0">
                                    <flux:icon.user class="w-4 h-4 text-zinc-400 dark:text-zinc-500" />
                                </div>
                            @endif
                            <div class="min-w-0">
                                <flux:text class="font-semibold text-sm truncate">{{ $member->name }}</flux:text>
                                @if ($member->phone_number)
                                    <flux:text class="text-xs truncate text-zinc-500 dark:text-zinc-400">{{ $member->phone_number }}</flux:text>
                                @endif
                            </div>
                        </button>
                        <div class="flex items-center gap-0.5 shrink-0">
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
        @endif
    </flux:card>

    {{-- Col 2: Badge preview --}}
    <flux:card class="p-3">
        <div
            x-show="selectedMemberId === null"
            class="flex flex-col items-center justify-center min-h-48 rounded-lg border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-center p-6 gap-2"
        >
            <flux:icon name="identification" class="size-7 text-zinc-300 dark:text-zinc-600" />
            <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">Select a member to preview badge</flux:text>
        </div>

        @foreach ($members as $member)
            <div x-show="selectedMemberId === {{ $member->id }}" x-cloak wire:key="preview-{{ $member->id }}">
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ route('exhibitor.badges.inline', [$booking, $member]) }}"
                        alt="Badge — {{ $member->name }}"
                        class="w-full h-auto"
                        loading="lazy">
                </div>
            </div>
        @endforeach
    </flux:card>

    {{-- Col 3: Company info + QR --}}
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">{{ $booking->brand_name }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $booking->contact_person }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-1">
            @foreach ($booking->selected_stalls as $stall)
                <flux:badge size="sm" color="zinc">{{ $stall }}</flux:badge>
            @endforeach
        </div>

        <flux:separator />

        <div class="space-y-3">
            <flux:text class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Visitor Enquiry QR</flux:text>
            <div class="flex items-center gap-3">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 bg-white shrink-0">
                    {!! $qrSvg !!}
                </div>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Visitors scan this QR to enquire via WhatsApp</flux:text>
            </div>
            <a
                href="data:image/svg+xml;base64,{{ base64_encode($qrSvgDownload) }}"
                download="qr-{{ $bookingCode }}.svg"
                class="inline-flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400 hover:underline"
            >
                <flux:icon name="arrow-down-tray" class="size-3.5" />
                Download QR (SVG)
            </a>
        </div>
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
