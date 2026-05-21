@if ($lookupPerformed)
    @if ($foundVisitor)

        @if (!empty($foundVisitor['exhibition_title']))
            <div class="flex items-center gap-3 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                @if ($foundVisitor['exhibition_logo_url'])
                    <img src="{{ $foundVisitor['exhibition_logo_url'] }}" alt="{{ $foundVisitor['exhibition_title'] }}"
                        class="object-contain h-10 w-14 shrink-0">
                @else
                    <div class="flex items-center justify-center rounded-md size-10 bg-zinc-100 dark:bg-zinc-700 shrink-0">
                        <flux:icon name="building-storefront" class="size-5 text-zinc-500" />
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wide text-zinc-500">Exhibition</p>
                    <p class="text-sm font-semibold truncate">{{ $foundVisitor['exhibition_title'] }}</p>
                </div>
            </div>
        @endif

        {{-- Visitor card --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="p-4 bg-white dark:bg-zinc-800 flex items-center gap-3">
                <div class="flex items-center justify-center size-14 rounded-full shrink-0 font-bold text-xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                    {{ mb_strtoupper(mb_substr($foundVisitor['name'], 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-lg truncate leading-tight">{{ $foundVisitor['name'] }}</p>
                    <p class="text-sm text-zinc-500 font-mono">{{ $foundVisitor['phone_number'] }}</p>
                </div>
                <div class="shrink-0 flex flex-col items-end gap-1.5">
                    <flux:badge color="{{ $foundVisitor['status_color'] }}" size="sm">{{ $foundVisitor['status_label'] }}</flux:badge>
                    @if($foundVisitor['is_invited_guest'])
                        <flux:badge color="purple" size="sm">Invited Guest</flux:badge>
                    @endif
                </div>
            </div>

            @if($foundVisitor['company_name'])
                <div class="px-4 py-2.5 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <flux:icon name="building-office-2" class="size-4 shrink-0 text-zinc-400" />
                    <span class="truncate">{{ $foundVisitor['company_name'] }}{{ $foundVisitor['designation'] ? ' — '.$foundVisitor['designation'] : '' }}</span>
                </div>
            @endif

            <div class="px-4 py-2.5 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="map-pin" class="size-4 shrink-0 text-zinc-400" />
                    <span>{{ $foundVisitor['city'] }}, {{ $foundVisitor['state'] }}</span>
                </div>
                <span class="font-mono text-xs text-zinc-400">{{ $foundVisitor['registration_code'] }}</span>
            </div>
        </div>

        {{-- Entry / Exit status (paid visitors only) --}}
        @if($foundVisitor['is_paid'])
            @if($foundVisitor['entered_at'] && $foundVisitor['exited_at'])

                {{-- Exited --}}
                <flux:callout variant="danger" icon="arrow-left-end-on-rectangle">
                    <flux:callout.heading>Exited</flux:callout.heading>
                    <flux:callout.text>
                        Entered {{ $foundVisitor['entered_at'] }} · Exited {{ $foundVisitor['exited_at'] }}
                    </flux:callout.text>
                </flux:callout>

            @elseif($foundVisitor['entered_at'])

                {{-- Inside — show entry time quietly with exit button --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-4 py-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="clock" class="size-4 shrink-0 text-zinc-400" />
                        <span>Entered {{ $foundVisitor['entered_at'] }}</span>
                    </div>
                    <flux:button
                        wire:click="markExited"
                        wire:loading.attr="disabled"
                        wire:target="markExited"
                        variant="ghost"
                        size="sm"
                        icon="arrow-left-end-on-rectangle"
                        class="shrink-0"
                    >
                        <span wire:loading.remove wire:target="markExited">Mark Exit</span>
                        <span wire:loading wire:target="markExited">Marking…</span>
                    </flux:button>
                </div>

            @else

                {{-- Not yet entered --}}
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>Entry allowed</flux:callout.heading>
                    <flux:callout.text>Visitor is confirmed. Entry will be recorded on scan.</flux:callout.text>
                </flux:callout>

            @endif
        @else
            <flux:callout variant="warning" icon="information-circle">
                <flux:callout.heading>Entry not tracked</flux:callout.heading>
                <flux:callout.text>Entry/exit is only recorded for paid confirmed visitors.</flux:callout.text>
            </flux:callout>
        @endif

        {{-- Additional persons --}}
        @if(!empty($foundVisitor['additional_persons']))
            <div class="space-y-2">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide px-1">
                    Additional Persons ({{ count($foundVisitor['additional_persons']) }})
                </p>
                @foreach($foundVisitor['additional_persons'] as $index => $person)
                    @php
                        $personNumber  = $index + 1;
                        $personExited  = $person['entered_at'] && $person['exited_at'];
                        $personInside  = $person['entered_at'] && !$person['exited_at'];
                        $cardClass     = $personExited
                            ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20'
                            : ($personInside
                                ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20'
                                : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800');
                        $avatarClass   = $personExited
                            ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'
                            : ($personInside
                                ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300'
                                : 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300');
                    @endphp
                    <div wire:key="person-{{ $index }}" class="rounded-xl border overflow-hidden {{ $cardClass }}">
                        <div class="flex items-center gap-3 px-3 py-2.5">
                            <div class="flex items-center justify-center size-9 rounded-full shrink-0 text-sm font-bold {{ $avatarClass }}">
                                {{ mb_strtoupper(mb_substr($person['name'], 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold truncate">{{ $person['name'] }}</p>
                                @if(!empty($person['phone_number']))
                                    <p class="text-xs text-zinc-500 font-mono">{{ $person['phone_number'] }}</p>
                                @endif
                                @if($personExited)
                                    <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">
                                        In {{ $person['entered_at'] }} · Out {{ $person['exited_at'] }}
                                    </p>
                                @elseif($personInside)
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">
                                        Inside · {{ $person['entered_at'] }}
                                    </p>
                                @endif
                            </div>
                            @if($foundVisitor['is_paid'] && $personInside)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-left-end-on-rectangle"
                                    wire:click="markPersonExited({{ $personNumber }})"
                                    wire:loading.attr="disabled"
                                    wire:target="markPersonExited({{ $personNumber }})"
                                    class="shrink-0 text-xs"
                                >
                                    Exit
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <flux:button variant="ghost" icon="magnifying-glass" wire:click="resetLookup" class="w-full">
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
                    class="w-full text-left rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-950/20 transition-colors"
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
                            @if (!empty($match['exhibition_title']))
                                <span class="text-xs font-medium text-zinc-500 truncate max-w-[10rem]">{{ $match['exhibition_title'] }}</span>
                            @endif
                            <span class="text-xs text-zinc-400">{{ $match['city'] }}</span>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>

        <flux:button variant="ghost" icon="magnifying-glass" wire:click="resetLookup" class="w-full">
            Search Again
        </flux:button>

    @else

        <flux:callout variant="danger" icon="exclamation-circle">
            <flux:callout.heading>Not found</flux:callout.heading>
            <flux:callout.text>No visitor matches <span class="font-mono font-semibold">{{ $lookupCode }}</span>.</flux:callout.text>
        </flux:callout>

        <flux:button variant="ghost" icon="magnifying-glass" wire:click="resetLookup" class="w-full">
            Search Again
        </flux:button>

    @endif
@else

    <div class="flex flex-col items-center justify-center h-full min-h-48 gap-3 text-center py-12">
        <flux:icon name="shield-check" class="size-16 text-zinc-200 dark:text-zinc-700" />
        <flux:text class="text-zinc-400 dark:text-zinc-500 text-sm">Scan a QR code or search above</flux:text>
    </div>

@endif
