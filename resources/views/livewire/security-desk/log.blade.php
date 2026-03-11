@php
    $logStyles = [
        'entered' => [
            'card'    => 'bg-green-500 border-green-600',
            'bar'     => 'bg-white/40',
            'heading' => 'text-white',
            'sub'     => 'text-white/90',
            'time'    => 'text-white/75',
            'label'   => 'Entry allowed',
        ],
        're_entered' => [
            'card'    => 'bg-red-500 border-red-600',
            'bar'     => 'bg-white/40',
            'heading' => 'text-white',
            'sub'     => 'text-white/90',
            'time'    => 'text-white/75',
            'label'   => 'Re-entry',
        ],
        'already_entered' => [
            'card'    => 'bg-red-500 border-red-600',
            'bar'     => 'bg-white/40',
            'heading' => 'text-white',
            'sub'     => 'text-white/90',
            'time'    => 'text-white/75',
            'label'   => 'Already inside',
        ],
        'not_found' => [
            'card'    => 'bg-red-500 border-red-600',
            'bar'     => 'bg-white/40',
            'heading' => 'text-white',
            'sub'     => 'text-white/90',
            'time'    => 'text-white/75',
            'label'   => 'Not found',
        ],
    ];

    $logIcons = [
        'entered'          => 'check-circle',
        're_entered'       => 'arrow-path',
        'already_entered'  => 'exclamation-triangle',
        'not_found'        => 'x-circle',
    ];
@endphp

@if (!empty($scanLog))
    <div class="p-4 space-y-2">
        <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wide px-1 mb-3">
            Scan Log
        </p>

        @foreach ($scanLog as $entry)
            @php $s = $logStyles[$entry['type']] ?? $logStyles['not_found']; @endphp
            <div
                wire:key="log-{{ $entry['id'] }}"
                x-data="scanLogEntry('{{ $entry['id'] }}', {{ $entry['duration'] }})"
                x-init="start()"
                x-show="visible"
                x-transition:leave="transition duration-500"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="relative flex items-start gap-4 rounded-xl border px-5 py-4 overflow-hidden {{ $s['card'] }}"
            >
                {{-- Progress bar --}}
                <div
                    class="absolute bottom-0 left-0 h-1 transition-all ease-linear {{ $s['bar'] }}"
                    :style="`width: ${progress}%; transition-duration: 1s`"
                ></div>

                {{-- Icon --}}
                <flux:icon name="{{ $logIcons[$entry['type']] ?? 'x-circle' }}" class="size-8 shrink-0 mt-0.5 {{ $s['sub'] }}" />

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-xl truncate {{ $s['heading'] }}">{{ $s['label'] }}</p>
                    <p class="text-base truncate mt-1 {{ $s['sub'] }}">{{ $entry['name'] }}</p>
                    @if(!empty($entry['entered_at']))
                        <p class="text-sm font-mono truncate mt-1 {{ $s['sub'] }} opacity-75">Entered {{ $entry['entered_at'] }}</p>
                    @endif
                </div>

                {{-- Time + dismiss --}}
                <div class="shrink-0 flex flex-col items-end gap-2">
                    <button
                        @click="dismiss()"
                        class="text-white/60 hover:text-white transition-colors"
                        aria-label="Dismiss"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                    <span class="text-sm font-mono {{ $s['time'] }}">{{ $entry['time'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="flex flex-col items-center justify-center h-full min-h-48 gap-3 text-center py-12">
        <flux:icon name="shield-check" class="size-16 text-zinc-200 dark:text-zinc-700" />
        <flux:text class="text-zinc-400 dark:text-zinc-500 text-sm">Scan log will appear here</flux:text>
    </div>
@endif

@script
<script>
    Alpine.data('scanLogEntry', (id, duration) => ({
        visible: true,
        progress: 100,
        timer: null,

        start() {
            const step = 100 / duration;
            this.timer = setInterval(() => {
                this.progress = Math.max(0, this.progress - step);
                if (this.progress <= 0) {
                    this.dismiss();
                }
            }, 1000);
        },

        dismiss() {
            clearInterval(this.timer);
            this.visible = false;
            setTimeout(() => {
                @this.dismissLog(id);
            }, 600);
        },
    }));
</script>
@endscript
