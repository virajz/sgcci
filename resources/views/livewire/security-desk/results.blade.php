@if ($lookupPerformed)
    @if ($foundVisitor)

        {{-- Visitor card --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 flex items-center gap-3">
                <div class="flex items-center justify-center size-12 rounded-full shrink-0 font-bold text-lg bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                    {{ mb_strtoupper(mb_substr($foundVisitor['name'], 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-base truncate">{{ $foundVisitor['name'] }}</p>
                    <p class="text-sm text-zinc-500 font-mono">{{ $foundVisitor['phone_number'] }}</p>
                </div>
                <div class="shrink-0 flex flex-col items-end gap-1">
                    <flux:badge color="{{ $foundVisitor['status_color'] }}" size="sm">{{ $foundVisitor['status_label'] }}</flux:badge>
                    @if($foundVisitor['is_invited_guest'])
                        <flux:badge color="purple" size="sm">Invited</flux:badge>
                    @endif
                </div>
            </div>

            @if($foundVisitor['company_name'])
                <div class="px-4 py-2 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <flux:icon name="building-office-2" class="size-4 shrink-0" />
                    <span class="truncate">{{ $foundVisitor['company_name'] }}{{ $foundVisitor['designation'] ? ' — '.$foundVisitor['designation'] : '' }}</span>
                </div>
            @endif

            <div class="px-4 py-2 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
                <div class="flex items-center gap-2">
                    <flux:icon name="map-pin" class="size-4 shrink-0" />
                    <span>{{ $foundVisitor['city'] }}, {{ $foundVisitor['state'] }}</span>
                </div>
                <span class="font-mono text-xs text-zinc-400">{{ $foundVisitor['registration_code'] }}</span>
            </div>
        </div>

        {{-- Entry / Exit (paid visitors only) --}}
        @if($foundVisitor['is_paid'])
            @if($foundVisitor['entered_at'])

                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>Already entered</flux:callout.heading>
                    <flux:callout.text>Entered {{ $foundVisitor['entered_at'] }}
                        @if($foundVisitor['exited_at'])
                            · Exited {{ $foundVisitor['exited_at'] }}
                        @endif
                    </flux:callout.text>
                </flux:callout>

                @if(!$foundVisitor['exited_at'])
                    <flux:button
                        wire:click="markExited"
                        wire:loading.attr="disabled"
                        wire:target="markExited"
                        variant="ghost"
                        icon="arrow-left-circle"
                        class="w-full"
                    >
                        <span wire:loading.remove wire:target="markExited">Mark Exit</span>
                        <span wire:loading wire:target="markExited">Marking…</span>
                    </flux:button>
                @endif

            @else

                <flux:callout variant="warning" icon="clock">
                    <flux:callout.heading>Not entered yet</flux:callout.heading>
                    <flux:callout.text>Tap below to allow entry.</flux:callout.text>
                </flux:callout>

                <flux:button
                    wire:click="markEntered"
                    wire:loading.attr="disabled"
                    wire:target="markEntered"
                    variant="primary"
                    icon="check"
                    class="w-full !py-4 !text-base"
                >
                    <span wire:loading.remove wire:target="markEntered">Allow Entry</span>
                    <span wire:loading wire:target="markEntered">Marking…</span>
                </flux:button>

            @endif
        @endif

        {{-- Additional persons --}}
        @if(!empty($foundVisitor['additional_persons']))
            <div class="space-y-2">
                <flux:text class="text-xs text-zinc-500 font-medium uppercase tracking-wide">
                    Additional Persons ({{ count($foundVisitor['additional_persons']) }})
                </flux:text>
                @foreach($foundVisitor['additional_persons'] as $index => $person)
                    <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700" wire:key="person-{{ $index }}">
                        <div class="flex items-center justify-center size-8 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-xs font-bold shrink-0">
                            {{ mb_strtoupper(mb_substr($person['name'], 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $person['name'] }}</p>
                            @if(!empty($person['phone_number']))
                                <p class="text-xs text-zinc-500 font-mono">{{ $person['phone_number'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <flux:button variant="ghost" icon="arrow-left" wire:click="resetLookup" class="w-full">
            Search Again
        </flux:button>

    @elseif(!empty($matchedVisitors))

        <flux:callout variant="warning" icon="users">
            <flux:callout.heading>{{ count($matchedVisitors) }} visitors found</flux:callout.heading>
            <flux:callout.text>Tap the correct visitor below.</flux:callout.text>
        </flux:callout>

        <div class="space-y-2">
            @foreach($matchedVisitors as $index => $match)
                <button
                    wire:click="selectVisitor('{{ $match['registration_code'] }}')"
                    wire:key="match-{{ $index }}"
                    class="w-full text-left rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-950/20 transition-colors"
                >
                    <div class="p-3 flex items-center gap-3">
                        <div class="flex items-center justify-center size-10 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-sm font-bold shrink-0">
                            {{ mb_strtoupper(mb_substr($match['name'], 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $match['name'] }}</p>
                            <p class="text-xs text-zinc-500 font-mono">{{ $match['phone_number'] }}</p>
                            @if($match['company_name'])
                                <p class="text-xs text-zinc-400 truncate">{{ $match['company_name'] }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <flux:badge color="{{ $match['status_color'] }}" size="sm">{{ $match['status_label'] }}</flux:badge>
                            <span class="text-xs text-zinc-400">{{ $match['city'] }}</span>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>

        <flux:button variant="ghost" icon="arrow-left" wire:click="resetLookup" class="w-full">
            Search Again
        </flux:button>

    @else

        <flux:callout variant="danger" icon="exclamation-circle">
            <flux:callout.heading>Not found</flux:callout.heading>
            <flux:callout.text>No visitor matches <span class="font-mono font-semibold">{{ $lookupCode }}</span>.</flux:callout.text>
        </flux:callout>

    @endif
@else

    <div class="flex flex-col items-center justify-center py-16 gap-3 text-center">
        <flux:icon name="shield-check" class="size-12 text-zinc-300 dark:text-zinc-700" />
        <flux:text class="text-zinc-500">Scan a QR code or search above</flux:text>
    </div>

@endif
