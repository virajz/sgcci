<div>
    @if ($this->exhibitions->isNotEmpty())
        <flux:select
            wire:model.live="selectedId"
            variant="listbox"
            searchable
            placeholder="Select exhibition..."
            class="min-w-[360px]"
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
    @endif
</div>
