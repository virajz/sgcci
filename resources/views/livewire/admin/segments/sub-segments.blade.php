<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:button
                size="sm"
                variant="ghost"
                icon="arrow-left"
                :href="route('admin.segments.index')"
                wire:navigate
            >
                Back to Segments
            </flux:button>
            <flux:heading size="xl" class="mt-2">{{ $segment->name }} — Sub-segments</flux:heading>
        </div>
        <flux:button wire:click="openAddModal" icon="plus">Add Sub-segment</flux:button>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name..." icon="magnifying-glass"
            iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($subSegments->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Sort Order</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($subSegments as $subSegment)
                            <flux:table.row :key="$subSegment->id">
                                <flux:table.cell>
                                    <span class="font-semibold text-black dark:text-white">{{ $subSegment->name }}</span>
                                </flux:table.cell>

                                <flux:table.cell>{{ $subSegment->sort_order }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge :color="$subSegment->is_active ? 'green' : 'zinc'" size="sm">
                                        {{ $subSegment->is_active ? 'Active' : 'Inactive' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :icon="$subSegment->is_active ? 'eye-slash' : 'eye'"
                                            wire:click="toggleActive({{ $subSegment->id }})"
                                        >
                                            {{ $subSegment->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            wire:click="openEditModal({{ $subSegment->id }})">Edit</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" color="red"
                                            wire:click="confirmDelete({{ $subSegment->id }})">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $subSegments->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="list-bullet" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search)
                        No sub-segments found
                    @else
                        No sub-segments yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search)
                        Try adjusting your search query.
                    @else
                        Add a sub-segment to start populating this segment.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Add Modal --}}
    <flux:modal wire:model="showAddModal">
        <form wire:submit="addSubSegment" class="space-y-6">
            <flux:heading size="lg">Add Sub-segment</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="e.g. Textile Manufacturer" />
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
                <flux:button type="submit" variant="primary">Add Sub-segment</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal wire:model="showEditModal">
        <form wire:submit="updateSubSegment" class="space-y-6">
            <flux:heading size="lg">Edit Sub-segment</flux:heading>

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
                <flux:button type="submit" variant="primary">Update Sub-segment</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Sub-segment</flux:heading>
            <flux:text>
                Are you sure you want to delete this sub-segment? Visitor records that already used it will keep the value on their record.
            </flux:text>

            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteSubSegment">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
