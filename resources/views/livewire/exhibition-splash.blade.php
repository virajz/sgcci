<div>
    <flux:modal
        wire:model="open"
        :dismissible="false"
        :closable="false"
        class="max-w-md"
    >
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center rounded-lg size-10 bg-accent/10">
                    <flux:icon.building-office class="size-5 text-accent" />
                </div>
                <div>
                    <flux:heading size="lg">Choose an Exhibition</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        All data will be filtered for this exhibition.
                    </flux:text>
                </div>
            </div>

            <flux:field>
                <flux:label>Exhibition</flux:label>
                <flux:select
                    wire:model="selectedId"
                    variant="listbox"
                    searchable
                    placeholder="Select exhibition..."
                >
                    <x-slot name="empty"></x-slot>
                    @foreach ($this->exhibitions as $exhibition)
                        <flux:select.option :value="(string) $exhibition->id">
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                <flux:avatar
                                    circle
                                    size="xs"
                                    :src="$exhibition->logo_url"
                                    :name="$exhibition->title"
                                />
                                {{ $exhibition->title }}
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedId" />
                <flux:description>
                    You can switch exhibitions any time from the header.
                </flux:description>
            </flux:field>

            <div class="flex justify-end">
                <flux:button
                    variant="primary"
                    wire:click="confirm"
                    wire:loading.attr="disabled"
                    :disabled="$selectedId === ''"
                >
                    Continue
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
