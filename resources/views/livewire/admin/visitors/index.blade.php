<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Visitors</flux:heading>
        <livewire:admin.visitors.export-visitors />
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Search by name, company, email, phone, or code..." icon="magnifying-glass" iconVariant="outline"
            clearable class="md:max-w-md" />

        <flux:select wire:model.live="statusFilter" placeholder="All Statuses" class="md:max-w-xs" variant="listbox"
            searchable>
            <flux:select.option value="">All Statuses</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="overflow-hidden">
        @if ($visitors->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Reg. Code</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Company</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>City</flux:table.column>
                        <flux:table.column>Business Segment</flux:table.column>
                        <flux:table.column>People</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Payment</flux:table.column>
                        <flux:table.column>Registered</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($visitors as $visitor)
                            <flux:table.row :key="$visitor->id">
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $visitor->registration_code }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <a href="{{ route('admin.visitors.show', $visitor) }}" wire:navigate
                                        class="font-semibold text-black dark:text-white hover:underline">{{ $visitor->name }}</a>
                                    @if ($visitor->email)
                                        <div class="text-xs text-zinc-500">{{ $visitor->email }}</div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div>{{ $visitor->company_name }}</div>
                                    @if ($visitor->designation)
                                        <div class="text-xs text-zinc-500">{{ $visitor->designation }}</div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="font-mono">{{ $visitor->phone_number }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $visitor->city }}@if ($visitor->state)
                                        , {{ $visitor->state }}
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div>{{ $visitor->business_segment }}</div>
                                    @if ($visitor->sub_business_segment)
                                        <div class="text-xs text-zinc-500">{{ $visitor->sub_business_segment }}</div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @php $totalPersons = 1 + count($visitor->additional_persons ?? []); @endphp
                                    @if ($totalPersons > 1)
                                        <button wire:click="viewPersons({{ $visitor->id }})" class="cursor-pointer">
                                            <flux:badge color="blue" size="sm">{{ $totalPersons }} people
                                            </flux:badge>
                                        </button>
                                    @else
                                        <flux:badge color="zinc" size="sm">1 person</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="{{ $visitor->status->color() }}" size="sm">
                                        {{ $visitor->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($visitor->payment_amount)
                                        <div class="font-mono text-sm">
                                            ₹{{ number_format($visitor->payment_amount, 2) }}</div>
                                        @if ($visitor->payment_status)
                                            <div class="text-xs text-zinc-500">{{ $visitor->payment_status }}</div>
                                        @endif
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $visitor->created_at->format('M d, Y') }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:dropdown position="bottom end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal"
                                            icon-variant="mini" inset="top bottom"></flux:button>

                                        <flux:menu>
                                            <flux:menu.item icon="eye"
                                                :href="route('admin.visitors.show', $visitor)" wire:navigate>
                                                View
                                            </flux:menu.item>

                                            <flux:menu.item icon="paper-airplane"
                                                wire:click="sendWhatsApp({{ $visitor->id }})"
                                                :disabled="$visitor->status !== \App\VisitorRegistrationStatus::Confirmed">
                                                Send WhatsApp
                                            </flux:menu.item>

                                            <flux:menu.separator />

                                            <flux:menu.item icon="trash" variant="danger"
                                                wire:click="confirmDelete({{ $visitor->id }})">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $visitors->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="user-group" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search || $statusFilter)
                        No visitors found
                    @else
                        No visitors yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search || $statusFilter)
                        Try adjusting your search or filter criteria.
                    @else
                        Visitors will appear here once they register for an exhibition.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Visitor</flux:heading>
            <flux:text>
                Are you sure you want to delete this visitor registration? This action cannot be undone.
            </flux:text>

            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteVisitor">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Members Modal --}}
    <flux:modal wire:model="showPersonsModal">
        <div class="space-y-4">
            <flux:heading size="lg">Members</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">All people registered under
                <span class="font-semibold text-black dark:text-white">{{ $selectedVisitorName }}</span>
            </flux:text>
            <flux:separator />
            <div class="space-y-2">
                <div class="flex items-center gap-3 py-1">
                    <span
                        class="flex items-center justify-center text-xs font-semibold rounded-full w-7 h-7 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 shrink-0">1</span>
                    <div>
                        <flux:text class="font-medium">{{ $selectedVisitorName }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">Primary</flux:text>
                    </div>
                </div>
                @foreach ($selectedPersons as $index => $person)
                    <div class="flex items-center gap-3 py-1">
                        <span
                            class="flex items-center justify-center text-xs font-semibold text-blue-600 bg-blue-100 rounded-full w-7 h-7 dark:bg-blue-900 dark:text-blue-400 shrink-0">{{ $index + 2 }}</span>
                        <flux:text class="font-medium">{{ $person['name'] }}</flux:text>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showPersonsModal', false)">Close</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
