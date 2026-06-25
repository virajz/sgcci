<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Managing Committee</flux:heading>
        <div class="flex gap-2">
            @if ($exhibitionSelected)
                <flux:button wire:click="confirmSendAll" variant="primary" color="green" icon="chat-bubble-left-right">Send WhatsApp</flux:button>
            @endif
            <flux:button :href="route('admin.committee-members.import')" variant="ghost" icon="arrow-up-tray" wire:navigate>Import CSV</flux:button>
            <flux:button :href="route('admin.committee-members.import-photos')" variant="ghost" icon="photo" wire:navigate>Import Photos</flux:button>
            <flux:button wire:click="openAddModal" icon="plus">Add Member</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, membership no, or post..."
            icon="magnifying-glass" iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($members->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column>Photo</flux:table.column>
                        <flux:table.column>Mem No</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Post</flux:table.column>
                        <flux:table.column>Post for Badge</flux:table.column>
                        <flux:table.column>Mobile</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($members as $member)
                            <flux:table.row :key="$member->id" wire:key="member-{{ $member->id }}">
                                <flux:table.cell>
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ $member->sort_order }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($member->photo)
                                        <img src="{{ route('admin.committee-members.photo', $member) }}"
                                            alt="{{ $member->name }}"
                                            class="w-10 h-10 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700" />
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                            <flux:icon icon="user" size="sm" class="text-zinc-400" />
                                        </div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($member->membership_number)
                                        <flux:badge color="zinc" size="sm">{{ $member->membership_number }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-semibold text-black dark:text-white">{{ $member->name }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $member->post ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $member->post_for_badge ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-mono">{{ $member->mobile ?? '—' }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        @if ($exhibitionSelected)
                                            <flux:button size="sm" variant="ghost" icon="chat-bubble-left-right" color="green"
                                                wire:click="sendWhatsApp({{ $member->id }})"
                                                wire:confirm="Send the exhibition pass to {{ $member->name }} on WhatsApp?">WhatsApp</flux:button>
                                        @endif
                                        <flux:button size="sm" variant="ghost" icon="printer"
                                            :href="route('admin.committee-members.badge.print', $member)" target="_blank">Print Badge</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            wire:click="openEditModal({{ $member->id }})">Edit</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" color="red"
                                            wire:click="confirmDelete({{ $member->id }})">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $members->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="user-group" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search)
                        No members found
                    @else
                        No committee members yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search)
                        Try adjusting your search query.
                    @else
                        Add committee members or import from a CSV file.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Add Modal --}}
    <flux:modal wire:model="showAddModal" variant="flyout">
        <form wire:submit="addMember" class="space-y-6">
            <flux:heading size="lg">Add Committee Member</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Sort Order</flux:label>
                    <flux:input type="number" wire:model="sortOrder" placeholder="0" />
                    <flux:error name="sortOrder" />
                </flux:field>

                <flux:field>
                    <flux:label>Membership No</flux:label>
                    <flux:input wire:model="membershipNumber" placeholder="e.g. L1133" />
                    <flux:error name="membershipNumber" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Name <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="name" placeholder="Full name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Post</flux:label>
                <flux:input wire:model="post" placeholder="e.g. President" />
                <flux:error name="post" />
            </flux:field>

            <flux:field>
                <flux:label>Post for Badge</flux:label>
                <flux:input wire:model="postForBadge" placeholder="e.g. President" />
                <flux:error name="postForBadge" />
            </flux:field>

            <flux:field>
                <flux:label>Mobile</flux:label>
                <flux:input wire:model="mobile" placeholder="Mobile number" />
                <flux:error name="mobile" />
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Add Member</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal wire:model="showEditModal" variant="flyout">
        <form wire:submit="updateMember" class="space-y-6">
            <flux:heading size="lg">Edit Committee Member</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Sort Order</flux:label>
                    <flux:input type="number" wire:model="sortOrder" placeholder="0" />
                    <flux:error name="sortOrder" />
                </flux:field>

                <flux:field>
                    <flux:label>Membership No</flux:label>
                    <flux:input wire:model="membershipNumber" placeholder="e.g. L1133" />
                    <flux:error name="membershipNumber" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Name <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="name" placeholder="Full name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Post</flux:label>
                <flux:input wire:model="post" placeholder="e.g. President" />
                <flux:error name="post" />
            </flux:field>

            <flux:field>
                <flux:label>Post for Badge</flux:label>
                <flux:input wire:model="postForBadge" placeholder="e.g. President" />
                <flux:error name="postForBadge" />
            </flux:field>

            <flux:field>
                <flux:label>Mobile</flux:label>
                <flux:input wire:model="mobile" placeholder="Mobile number" />
                <flux:error name="mobile" />
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Update Member</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Committee Member</flux:heading>
            <flux:text>Are you sure you want to delete this committee member? This action cannot be undone.</flux:text>

            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteMember">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Send WhatsApp to All Confirmation Modal --}}
    <flux:modal wire:model="showSendAllModal">
        <div class="space-y-6">
            <flux:heading size="lg">Send WhatsApp to All</flux:heading>
            <flux:text>This will register every committee member for the selected exhibition and send each one their entry pass on WhatsApp. Members without a mobile number are skipped.</flux:text>

            <div class="flex gap-2">
                <flux:button variant="primary" color="green" icon="chat-bubble-left-right"
                    wire:click="sendWhatsAppToAll" wire:loading.attr="disabled" wire:target="sendWhatsAppToAll">
                    <span wire:loading.remove wire:target="sendWhatsAppToAll">Send to All</span>
                    <span wire:loading wire:target="sendWhatsAppToAll">Sending...</span>
                </flux:button>
                <flux:button variant="ghost" wire:click="$set('showSendAllModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
