<div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Exhibitions</flux:heading>
        <flux:button wire:click="openAddModal" icon="plus">Add Exhibition</flux:button>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name..." icon="magnifying-glass"
            iconVariant="outline" class="md:max-w-md" />
    </div>

    <flux:card class="overflow-hidden">
        @if ($exhibitions->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Dates</flux:table.column>
                        <flux:table.column>Entry</flux:table.column>
                        <flux:table.column>Added By</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($exhibitions as $exhibition)
                            <flux:table.row :key="$exhibition->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-black dark:text-white">{{ $exhibition->title }}</span>
                                        @if ($exhibition->registration_closed)
                                            <flux:badge color="red" size="sm">Closed</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="text-sm">
                                        {{ $exhibition->start_date->format('M d, Y') }} — {{ $exhibition->end_date->format('M d, Y') }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge :color="$exhibition->entry_type?->value === 'paid' ? 'amber' : 'green'" size="sm">
                                        {{ $exhibition->entry_type?->label() ?? 'Free' }}
                                        @if ($exhibition->entry_type?->value === 'paid' && $exhibition->entry_amount)
                                            - {{ Number::currency((float) $exhibition->entry_amount, 'INR') }}
                                        @endif
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $exhibition->createdBy?->name ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button size="sm" variant="ghost" icon="qr-code"
                                            wire:click="showQrCode({{ $exhibition->id }})">QR Code</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="link"
                                            x-data="{ url: '{{ url('/' . $exhibition->slug . '/visitors-registration') }}' }"
                                            x-on:click="navigator.clipboard.writeText(url); $flux.toast('Registration link copied to clipboard')">
                                            Copy Link
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :icon="$exhibition->registration_closed ? 'lock-open' : 'lock-closed'"
                                            :color="$exhibition->registration_closed ? 'green' : 'amber'"
                                            wire:click="confirmToggleRegistration({{ $exhibition->id }})"
                                        >
                                            {{ $exhibition->registration_closed ? 'Open Reg.' : 'Close Reg.' }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            wire:click="openEditModal({{ $exhibition->id }})">Edit</flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" color="red"
                                            wire:click="confirmDelete({{ $exhibition->id }})">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $exhibitions->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon icon="building-office" size="xl" class="mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    @if ($search)
                        No exhibitions found
                    @else
                        No exhibitions yet
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($search)
                        Try adjusting your search query.
                    @else
                        Add exhibitions to manage visitor registrations.
                    @endif
                </flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Add Exhibition Modal --}}
    <flux:modal wire:model="showAddModal" variant="flyout">
        <form wire:submit="addExhibition" class="space-y-6">
            <flux:heading size="lg">Add Exhibition</flux:heading>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" placeholder="Exhibition title" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" placeholder="Exhibition description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Start Date</flux:label>
                    <flux:input wire:model="startDate" type="date" />
                    <flux:error name="startDate" />
                </flux:field>

                <flux:field>
                    <flux:label>End Date</flux:label>
                    <flux:input wire:model="endDate" type="date" />
                    <flux:error name="endDate" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Entry Type</flux:label>
                <flux:radio.group wire:model.live="entryType">
                    <flux:radio value="free" label="Free Entry" />
                    <flux:radio value="paid" label="Paid Entry" />
                </flux:radio.group>
                <flux:error name="entryType" />
            </flux:field>

            @if ($entryType === 'paid')
                <flux:field>
                    <flux:label>Entry Amount (INR)</flux:label>
                    <flux:input wire:model="entryAmount" type="number" min="1" step="0.01"
                        placeholder="e.g. 100.00" />
                    <flux:error name="entryAmount" />
                </flux:field>
            @endif

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Add Exhibition</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showAddModal', false)">Cancel
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Exhibition Modal --}}
    <flux:modal wire:model="showEditModal" variant="flyout">
        <form wire:submit="updateExhibition" class="space-y-6">
            <flux:heading size="lg">Edit Exhibition</flux:heading>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" placeholder="Exhibition title" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" placeholder="Exhibition description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Start Date</flux:label>
                    <flux:input wire:model="startDate" type="date" />
                    <flux:error name="startDate" />
                </flux:field>

                <flux:field>
                    <flux:label>End Date</flux:label>
                    <flux:input wire:model="endDate" type="date" />
                    <flux:error name="endDate" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Entry Type</flux:label>
                <flux:radio.group wire:model.live="entryType">
                    <flux:radio value="free" label="Free Entry" />
                    <flux:radio value="paid" label="Paid Entry" />
                </flux:radio.group>
                <flux:error name="entryType" />
            </flux:field>

            @if ($entryType === 'paid')
                <flux:field>
                    <flux:label>Entry Amount (INR)</flux:label>
                    <flux:input wire:model="entryAmount" type="number" min="1" step="0.01"
                        placeholder="e.g. 100.00" />
                    <flux:error name="entryAmount" />
                </flux:field>
            @endif

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Update Exhibition</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Cancel
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Exhibition</flux:heading>
            <flux:text>
                Are you sure you want to delete this exhibition? This action cannot be undone.
            </flux:text>

            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteExhibition">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Toggle Registration Modal --}}
    <flux:modal wire:model="showToggleRegistrationModal">
        @php $toggleExhibition = $exhibitionToToggleRegistration ? \App\Models\Exhibition::find($exhibitionToToggleRegistration) : null; @endphp
        <div class="space-y-6">
            @if ($toggleExhibition?->registration_closed)
                <flux:heading size="lg">Re-open Visitor Registration</flux:heading>
                <flux:text>
                    Are you sure you want to re-open visitor registration for <strong>{{ $toggleExhibition->title }}</strong>? Visitors will be able to register again.
                </flux:text>
                <div class="flex gap-2">
                    <flux:button variant="primary" wire:click="toggleRegistrationClosed">Re-open Registration</flux:button>
                    <flux:button variant="ghost" wire:click="$set('showToggleRegistrationModal', false)">Cancel</flux:button>
                </div>
            @else
                <flux:heading size="lg">Close Visitor Registration</flux:heading>
                <flux:text>
                    Are you sure you want to close visitor registration for <strong>{{ $toggleExhibition?->title }}</strong>? Visitors will see a "Thank You" message instead of the registration form.
                </flux:text>
                <div class="flex gap-2">
                    <flux:button variant="danger" wire:click="toggleRegistrationClosed">Close Registration</flux:button>
                    <flux:button variant="ghost" wire:click="$set('showToggleRegistrationModal', false)">Cancel</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- QR Code Modal --}}
    <flux:modal wire:model="showQrModal">
        <div class="space-y-6" x-data="{ copied: false }">
            <flux:heading size="lg">Visitor Registration QR Code</flux:heading>
            <flux:text>{{ $qrExhibitionTitle }}</flux:text>

            <flux:field class="">
                <flux:label>Tracking Source (optional)</flux:label>
                <flux:input wire:model.live.debounce.500ms="qrSource" placeholder="e.g. whatsapp, flyer, email" />
                <flux:text class="mt-2 text-xs text-zinc-500">Add a source to track where visitors come from. Updates
                    the QR code and URL automatically.</flux:text>
            </flux:field>

            <div id="qr-code-container" class="flex justify-center p-4 bg-white rounded-lg" wire:key="qr-svg-{{ $qrExhibitionId }}">
                {!! $qrCodeSvg !!}
            </div>

            <flux:field>
                <flux:label>Registration URL</flux:label>
                <div class="flex gap-2">
                    <flux:input readonly :value="$qrCodeUrl" class="grow" />
                    <flux:button variant="ghost" icon="clipboard"
                        x-on:click="
                            navigator.clipboard.writeText(@js($qrCodeUrl));
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        ">
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" x-cloak>Copied!</span>
                    </flux:button>
                </div>
            </flux:field>

            <div class="flex justify-between gap-2">
                <div class="flex gap-2">
                    <flux:button variant="primary" icon="arrow-down-tray"
                        x-on:click="
                            const container = document.getElementById('qr-code-container');
                            const svg = container.querySelector('svg');
                            if (!svg) return;
                            const svgData = new XMLSerializer().serializeToString(svg);
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            const img = new Image();
                            img.onload = function() {
                                canvas.width = 1024;
                                canvas.height = 1024;
                                ctx.fillStyle = '#ffffff';
                                ctx.fillRect(0, 0, 1024, 1024);
                                ctx.drawImage(img, 0, 0, 1024, 1024);
                                const link = document.createElement('a');
                                link.download = '{{ Str::slug($qrExhibitionTitle) }}-qr-code.png';
                                link.href = canvas.toDataURL('image/png');
                                link.click();
                            };
                            img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
                        ">
                        Download PNG
                    </flux:button>
                    <flux:button variant="ghost" icon="arrow-down-tray"
                        x-on:click="
                            const container = document.getElementById('qr-code-container');
                            const svg = container.querySelector('svg');
                            if (!svg) return;
                            const svgData = new XMLSerializer().serializeToString(svg);
                            const blob = new Blob([svgData], { type: 'image/svg+xml' });
                            const link = document.createElement('a');
                            link.download = '{{ Str::slug($qrExhibitionTitle) }}-qr-code.svg';
                            link.href = URL.createObjectURL(blob);
                            link.click();
                            URL.revokeObjectURL(link.href);
                        ">
                        Download SVG
                    </flux:button>
                </div>
                <flux:button variant="ghost" wire:click="$set('showQrModal', false)">Close</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
