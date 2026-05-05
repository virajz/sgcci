<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Segments</flux:heading>
        <flux:button wire:click="openAddModal" icon="plus">Add Segment</flux:button>
    </div>

    <flux:text>
        Master list of business segments shown on the visitor registration form. Each segment can have its own list of sub-segments.
    </flux:text>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name..." icon="magnifying-glass"
            iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($segments->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Sort Order</flux:table.column>
                        <flux:table.column>Sub-segments</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($segments as $segment)
                            <flux:table.row :key="$segment->id">
                                <flux:table.cell>
                                    <span class="font-semibold text-black dark:text-white">{{ $segment->name }}</span>
                                </flux:table.cell>

                                <flux:table.cell>{{ $segment->sort_order }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="list-bullet"
                                        :href="route('admin.segments.sub-segments', $segment)"
                                        wire:navigate
                                    >
                                        {{ $segment->sub_segments_count }} sub-segment{{ $segment->sub_segments_count === 1 ? '' : 's' }}
                                    </flux:button>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge :color="$segment->is_active ? 'green' : 'zinc'" size="sm">
                                        {{ $segment->is_active ? 'Active' : 'Inactive' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :icon="$segment->is_active ? 'eye-slash' : 'eye'"
                                            wire:click="toggleActive({{ $segment->id }})"
                                        >
                                            {{ $segment->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            wire:click="openEditModal({{ $segment->id }})">Edit</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" color="red"
                                            wire:click="confirmDelete({{ $segment->id }})">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $segments->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="tag" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search)
                        No segments found
                    @else
                        No segments yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search)
                        Try adjusting your search query.
                    @else
                        Add a segment to start building the master list.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Add Modal --}}
    <flux:modal wire:model="showAddModal">
        <form wire:submit="addSegment" class="space-y-6">
            <flux:heading size="lg">Add Segment</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="e.g. Textile" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Sort Order</flux:label>
                <flux:description>Lower numbers appear first.</flux:description>
                <flux:input wire:model="sortOrder" type="number" min="0" max="9999" />
                <flux:error name="sortOrder" />
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="isActive" label="Active" />
                <flux:error name="isActive" />
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Add Segment</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal wire:model="showEditModal">
        <form wire:submit="updateSegment" class="space-y-6">
            <flux:heading size="lg">Edit Segment</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Sort Order</flux:label>
                <flux:input wire:model="sortOrder" type="number" min="0" max="9999" />
                <flux:error name="sortOrder" />
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="isActive" label="Active" />
                <flux:error name="isActive" />
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Update Segment</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Segment</flux:heading>
            <flux:text>
                Are you sure you want to delete this segment? All sub-segments under it will also be removed. Visitor records that already used this segment will keep the segment name on their record.
            </flux:text>

            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteSegment">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
