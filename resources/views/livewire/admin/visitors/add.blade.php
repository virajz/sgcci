<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Add Visitors</flux:heading>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.visitors.index') }}" wire:navigate>
            Back to Visitors
        </flux:button>
    </div>

    @if ($done)
        <flux:callout variant="{{ $addedCount > 0 ? 'success' : 'warning' }}" icon="{{ $addedCount > 0 ? 'check-circle' : 'exclamation-triangle' }}">
            <flux:callout.heading>
                {{ $addedCount }} visitor{{ $addedCount !== 1 ? 's' : '' }} added
                @if ($skippedCount > 0)
                    · {{ $skippedCount }} skipped (already registered)
                @endif
            </flux:callout.heading>
            <flux:callout.text>WhatsApp passes have been queued for delivery.</flux:callout.text>
        </flux:callout>

        <flux:button wire:click="reset_" icon="plus">Add More</flux:button>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                        <th class="pb-2 font-medium text-zinc-500 w-8">#</th>
                        <th class="pb-2 font-medium text-zinc-500">Name <span class="text-red-500">*</span></th>
                        <th class="pb-2 font-medium text-zinc-500">Phone <span class="text-red-500">*</span></th>
                        <th class="pb-2 font-medium text-zinc-500">Type</th>
                        <th class="pb-2 w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($rows as $index => $row)
                        <tr wire:key="row-{{ $index }}">
                            <td class="py-2 pr-2 text-zinc-400 text-xs">{{ $index + 1 }}</td>
                            <td class="py-2 pr-2">
                                <flux:input
                                    wire:model="rows.{{ $index }}.name"
                                    placeholder="Full name"
                                    size="sm"
                                />
                            </td>
                            <td class="py-2 pr-2">
                                <flux:input
                                    wire:model="rows.{{ $index }}.phone_number"
                                    type="tel"
                                    placeholder="9876543210"
                                    size="sm"
                                />
                            </td>
                            <td class="py-2 pr-2">
                                <flux:select
                                    wire:model="rows.{{ $index }}.visitor_type"
                                    variant="listbox"
                                    placeholder="Standard"
                                    size="sm"
                                    class="min-w-28"
                                >
                                    <flux:select.option value="">Standard</flux:select.option>
                                    <flux:select.option value="press">Press & Media</flux:select.option>
                                    <flux:select.option value="vip">VIP</flux:select.option>
                                    <flux:select.option value="vendor">Vendor</flux:select.option>
                                </flux:select>
                            </td>
                            <td class="py-2">
                                <flux:button
                                    wire:click="removeRow({{ $index }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="x-mark"
                                    class="text-zinc-400 hover:text-red-500"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <flux:button wire:click="addRows" variant="ghost" icon="plus" size="sm">
                Add 5 more rows
            </flux:button>

            <flux:button
                wire:click="save"
                variant="primary"
                wire:loading.attr="disabled"
                wire:target="save"
                icon="check"
            >
                <span wire:loading.remove wire:target="save">Register & Send WhatsApp</span>
                <span wire:loading wire:target="save">Registering...</span>
            </flux:button>
        </div>

        <flux:text class="text-xs text-zinc-400">
            All visitors will be registered as confirmed with Gujarat, Surat as location. Empty rows and already-registered phone numbers are skipped.
        </flux:text>
    @endif
</div>
