<div>
    <flux:button wire:click="openModal" variant="ghost" icon="document-chart-bar" iconVariant="outline">
        Reports
    </flux:button>

    <flux:modal wire:model="showModal" class="min-w-[40rem]">
        <form wire:submit="export">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Export Bookings</flux:heading>
                    <flux:text class="mt-2">
                        Select the criteria for your export. The system will generate a CSV file with the selected
                        data.
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

                {{-- Status Filter --}}
                <flux:field>
                    <flux:label>Booking Status (Optional)</flux:label>
                    <flux:text class="text-xs text-zinc-500 mb-2">
                        Leave empty to export all statuses
                    </flux:text>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:checkbox wire:model.live="selectedStatuses" value="pending_approval" label="Pending Approval" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="approved_by_admin" label="Approved by Admin" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="allotted" label="Allotted" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="payment_pending" label="Payment Pending" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="payment_completed" label="Payment Completed" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="rejected" label="Rejected" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="cancelled" label="Cancelled" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="expired" label="Expired" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="refunded" label="Refunded" />
                        <flux:checkbox wire:model.live="selectedStatuses" value="manual_block" label="Manual Block" />
                    </div>
                </flux:field>

                {{-- Data Options --}}
                <flux:field>
                    <flux:label>Include in Export</flux:label>
                    <div class="flex flex-col gap-2 mt-2">
                        <flux:checkbox wire:model="includeContactInfo" label="Contact Information (Email, Phone, City, GST)" />
                        <flux:checkbox wire:model="includeMembershipInfo" label="Membership Information" />
                        <flux:checkbox wire:model="includePaymentInfo" label="Payment Information" />
                    </div>
                </flux:field>

                {{-- Result Count --}}
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
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
