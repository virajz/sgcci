<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Staff Members</flux:heading>
        <flux:button wire:click="openAddModal" icon="plus">Add Staff Member</flux:button>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or phone number..."
            icon="magnifying-glass" iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($staffMembers->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Phone Number</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($staffMembers as $staff)
                            <flux:table.row :key="$staff->id">
                                <flux:table.cell>
                                    <div class="font-semibold text-black dark:text-white">{{ $staff->name }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-mono">{{ $staff->phone_code }} {{ $staff->phone_number }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($staff->is_active)
                                        <flux:badge color="green" size="sm">Active</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $staff->created_at->format('M d, Y') }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            wire:click="openEditModal({{ $staff->id }})">Edit</flux:button>
                                        <flux:button size="sm" variant="ghost"
                                            icon="{{ $staff->is_active ? 'pause' : 'play' }}"
                                            wire:click="toggleActive({{ $staff->id }})">
                                            {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" color="red"
                                            wire:click="confirmDelete({{ $staff->id }})">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $staffMembers->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="users" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search)
                        No staff members found
                    @else
                        No staff members yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search)
                        Try adjusting your search query.
                    @else
                        Add staff members to receive WhatsApp notifications for new bookings.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Add Staff Modal --}}
    <flux:modal wire:model="showAddModal" variant="flyout">
        <form wire:submit="addStaffMember" class="space-y-6">
            <flux:heading size="lg">Add Staff Member</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="Staff member name" />
                <flux:error name="name" />
            </flux:field>

            <div class="grid grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Country Code</flux:label>
                    <flux:input wire:model="phoneCode" placeholder="+91" />
                    <flux:error name="phoneCode" />
                </flux:field>

                <div class="col-span-2">
                    <flux:field>
                        <flux:label>Phone Number</flux:label>
                        <flux:input wire:model="phoneNumber" placeholder="Phone number" />
                        <flux:error name="phoneNumber" />
                    </flux:field>
                </div>
            </div>

            <flux:field>
                <flux:checkbox wire:model="isActive">Active</flux:checkbox>
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Add Staff Member</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showAddModal', false)">Cancel
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Staff Modal --}}
    <flux:modal wire:model="showEditModal" variant="flyout">
        <form wire:submit="updateStaffMember" class="space-y-6">
            <flux:heading size="lg">Edit Staff Member</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="Staff member name" />
                <flux:error name="name" />
            </flux:field>

            <div class="grid grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Country Code</flux:label>
                    <flux:input wire:model="phoneCode" placeholder="+91" />
                    <flux:error name="phoneCode" />
                </flux:field>

                <div class="col-span-2">
                    <flux:field>
                        <flux:label>Phone Number</flux:label>
                        <flux:input wire:model="phoneNumber" placeholder="Phone number" />
                        <flux:error name="phoneNumber" />
                    </flux:field>
                </div>
            </div>

            <flux:field>
                <flux:checkbox wire:model="isActive">Active</flux:checkbox>
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Update Staff Member</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Cancel
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Staff Member</flux:heading>
            <flux:text>
                Are you sure you want to delete this staff member? This action cannot be undone.
            </flux:text>

            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteStaffMember">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
