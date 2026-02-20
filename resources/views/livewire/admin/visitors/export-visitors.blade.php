<div>
    <flux:button wire:click="openModal" icon="arrow-down-tray">
        Export Visitors
    </flux:button>

    <flux:modal wire:model="showModal" class="min-w-[40rem]">
        <form wire:submit="export">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Export Visitors</flux:heading>
                    <flux:text class="mt-2">
                        Configure your export options. The system will generate a CSV file with the selected data.
                    </flux:text>
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>From Date</flux:label>
                        <flux:date-picker wire:model.live="dateFrom" />
                    </flux:field>

                    <flux:field>
                        <flux:label>To Date</flux:label>
                        <flux:date-picker wire:model.live="dateTo" />
                    </flux:field>
                </div>

                {{-- Exhibition Filter --}}
                <flux:field>
                    <flux:label badge="optional">Exhibition</flux:label>
                    <flux:select wire:model.live="exhibitionId" placeholder="All exhibitions" variant="listbox"
                        searchable>
                        <option value="">All exhibitions</option>
                        @foreach ($exhibitions as $exhibition)
                            <option value="{{ $exhibition->id }}">{{ $exhibition->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- Status Filter --}}
                <flux:field>
                    <flux:label badge="optional">Visitor Status</flux:label>
                    <flux:text class="mb-2 text-xs text-zinc-500">
                        Leave empty to export all statuses
                    </flux:text>
                    <div class="flex flex-col gap-2">
                        @foreach ($statuses as $status)
                            <flux:checkbox wire:model.live="selectedStatuses" value="{{ $status->value }}"
                                label="{{ $status->label() }}" />
                        @endforeach
                    </div>
                </flux:field>

                {{-- Data Inclusion Options --}}
                <flux:field>
                    <flux:label badge="optional">Include in Export</flux:label>
                    <div class="flex flex-col gap-2 mt-2">
                        <flux:checkbox wire:model="includePersonalInfo"
                            label="Personal Information (Name, Email, Phone, Designation)" />
                        <flux:checkbox wire:model="includeBusinessInfo"
                            label="Business Information (Company, Segment, City, State)" />
                        <flux:checkbox wire:model="includePaymentInfo"
                            label="Payment Information (Amount, Status, Method, Transaction ID)" />
                        <flux:checkbox wire:model="includeAdditionalPersons" label="Additional Persons Details" />
                    </div>
                </flux:field>

                {{-- Result Count --}}
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <flux:text class="font-medium">Records to export:</flux:text>
                        <flux:badge size="lg" color="zinc" variant="solid">
                            {{ number_format($resultCount) }}
                        </flux:badge>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="arrow-down-tray" iconVariant="outline"
                        :disabled="$resultCount === 0">
                        Export CSV
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
