<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">SGCCI Members</flux:heading>
        <div class="flex gap-2">
            @if ($exhibitionSelected)
                <flux:button wire:click="confirmSendAll" variant="primary" color="green" icon="chat-bubble-left-right">Send WhatsApp</flux:button>
            @endif
            <flux:button :href="route('admin.members.import')" variant="ghost" icon="arrow-up-tray" wire:navigate>Import CSV</flux:button>
            <flux:button :href="route('admin.members.create')" icon="plus" wire:navigate>Add Member</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, membership no, company, or phone..."
            icon="magnifying-glass" iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($members->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Membership No</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Company</flux:table.column>
                        <flux:table.column>Post</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Cell</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($members as $member)
                            <flux:table.row :key="$member->id" wire:key="member-{{ $member->id }}">
                                <flux:table.cell>
                                    <flux:badge color="zinc" size="sm">{{ $member->membership_number }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-semibold text-black dark:text-white">{{ $member->contact_name }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $member->company ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $member->post ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($member->type)
                                        <flux:badge color="blue" size="sm">{{ $member->type }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-mono text-sm">{{ $member->cell_no ?? '—' }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        @if ($exhibitionSelected)
                                            <flux:button size="sm" variant="ghost" icon="chat-bubble-left-right" color="green"
                                                wire:click="sendWhatsApp({{ $member->id }})"
                                                wire:confirm="Send the exhibition pass to {{ $member->contact_name }} on WhatsApp?">WhatsApp</flux:button>
                                        @endif
                                        <flux:button size="sm" variant="ghost" icon="printer"
                                            :href="route('admin.members.badge.print', $member)" target="_blank">Print Badge</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            :href="route('admin.members.show', $member)" wire:navigate>Edit</flux:button>
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
                <flux:icon icon="users" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search)
                        No members found
                    @else
                        No members yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search)
                        Try adjusting your search query.
                    @else
                        Add members manually or import from a CSV file.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Send WhatsApp to All Confirmation Modal --}}
    <flux:modal wire:model="showSendAllModal">
        <div class="space-y-6">
            <flux:heading size="lg">Send WhatsApp to All</flux:heading>
            <flux:text>This will register every member for the selected exhibition and send each one their entry pass on WhatsApp. Members without a cell number are skipped.</flux:text>

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
