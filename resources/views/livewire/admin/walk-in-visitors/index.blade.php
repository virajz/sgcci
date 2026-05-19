<div class="space-y-6"
    x-data
    x-on:open-print-window.window="window.open($event.detail.url, '_blank')"
>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Walk-in Visitors</flux:heading>
            @if ($exhibition)
                <flux:text class="text-zinc-500">{{ $exhibition->title }}</flux:text>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @if (count($selectedIds) > 0)
                <flux:button variant="primary" icon="printer" iconVariant="outline" wire:click="printSelected">
                    Print Selected ({{ count($selectedIds) }})
                </flux:button>
            @endif
            <flux:button variant="filled" icon="plus" iconVariant="outline" wire:click="createPressVisitor">
                Press Visitor
            </flux:button>
            <flux:button variant="filled" icon="plus" iconVariant="outline" wire:click="createVipVisitor">
                VIP
            </flux:button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Search by name, company, email, phone, or code..." icon="magnifying-glass" iconVariant="outline"
            clearable class="md:max-w-md" />

        <div class="flex items-center gap-1">
            <flux:button size="sm" :variant="$visitorTypeFilter === '' ? 'filled' : 'ghost'" wire:click="$set('visitorTypeFilter', '')">All <flux:badge size="sm" class="ml-1">{{ $totalCount }}</flux:badge></flux:button>
            <flux:button size="sm" :variant="$visitorTypeFilter === 'standard' ? 'filled' : 'ghost'" wire:click="$set('visitorTypeFilter', 'standard')">Standard <flux:badge size="sm" class="ml-1">{{ $typeCounts->get('standard', 0) }}</flux:badge></flux:button>
            <flux:button size="sm" :variant="$visitorTypeFilter === 'vip' ? 'filled' : 'ghost'" wire:click="$set('visitorTypeFilter', 'vip')">VIP <flux:badge size="sm" class="ml-1">{{ $typeCounts->get('vip', 0) }}</flux:badge></flux:button>
            <flux:button size="sm" :variant="$visitorTypeFilter === 'press' ? 'filled' : 'ghost'" wire:click="$set('visitorTypeFilter', 'press')">Press <flux:badge size="sm" class="ml-1">{{ $typeCounts->get('press', 0) }}</flux:badge></flux:button>
            <flux:button size="sm" :variant="$visitorTypeFilter === 'vendor' ? 'filled' : 'ghost'" wire:click="$set('visitorTypeFilter', 'vendor')">Vendor <flux:badge size="sm" class="ml-1">{{ $typeCounts->get('vendor', 0) }}</flux:badge></flux:button>
        </div>
    </div>

    <flux:card class="overflow-hidden">
        @if ($visitors->count() > 0)
            <div class="overflow-x-auto">
                <flux:checkbox.group wire:model.live="selectedIds">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-10"><flux:checkbox.all /></flux:table.column>
                        <flux:table.column>Reg. Code</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Company</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>City</flux:table.column>
                        <flux:table.column>Business Segment</flux:table.column>
                        <flux:table.column>People</flux:table.column>
                        <flux:table.column>Registered</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($visitors as $visitor)
                            <flux:table.row :key="$visitor->id">
                                <flux:table.cell>
                                    <flux:checkbox :value="$visitor->id" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $visitor->registration_code }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-semibold text-black dark:text-white">{{ $visitor->name }}</div>
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
                                    {{ $visitor->city }}@if ($visitor->state), {{ $visitor->state }}@endif
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
                                            <flux:badge color="blue" size="sm">{{ $totalPersons }} people</flux:badge>
                                        </button>
                                    @else
                                        <flux:badge color="zinc" size="sm">1 person</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $visitor->created_at->format('M d, Y') }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center gap-1">
                                        <flux:tooltip content="Print Badge" position="top">
                                            <flux:button size="sm" variant="ghost" icon="printer" icon-variant="mini"
                                                inset="top bottom"
                                                href="{{ route('front-desk.visitor.badge.print', $visitor->registration_code) }}"
                                                target="_blank" />
                                        </flux:tooltip>

                                        <flux:dropdown position="bottom end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" icon-variant="mini" inset="top bottom"></flux:button>

                                            <flux:menu>
                                                <flux:menu.item icon="trash" variant="danger"
                                                    wire:click="confirmDelete({{ $visitor->id }})">
                                                    Delete
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                </flux:checkbox.group>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $visitors->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="user-group" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search || $visitorTypeFilter)
                        No walk-in visitors found
                    @else
                        No walk-in visitors yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search || $visitorTypeFilter)
                        Try adjusting your search or filter criteria.
                    @else
                        Walk-in visitors added by front desk staff will appear here.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Walk-in Visitor</flux:heading>
            <flux:text>
                Are you sure you want to delete this walk-in visitor registration? This action cannot be undone.
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
                    <span class="flex items-center justify-center text-xs font-semibold rounded-full w-7 h-7 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 shrink-0">1</span>
                    <div>
                        <flux:text class="font-medium">{{ $selectedVisitorName }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">Primary</flux:text>
                    </div>
                </div>
                @foreach ($selectedPersons as $index => $person)
                    <div class="flex items-center gap-3 py-1">
                        <span class="flex items-center justify-center text-xs font-semibold text-blue-600 bg-blue-100 rounded-full w-7 h-7 dark:bg-blue-900 dark:text-blue-400 shrink-0">{{ $index + 2 }}</span>
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
