@php
    $logStyles = [
        'entered' => [
            'card'    => 'bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-800',
            'bar'     => 'bg-green-400 dark:bg-green-600',
            'heading' => 'text-green-900 dark:text-green-100',
            'sub'     => 'text-green-700 dark:text-green-300',
            'time'    => 'text-green-600 dark:text-green-400',
            'label'   => 'Entry allowed',
        ],
        're_entered' => [
            'card'    => 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800',
            'bar'     => 'bg-red-400 dark:bg-red-600',
            'heading' => 'text-red-900 dark:text-red-100',
            'sub'     => 'text-red-700 dark:text-red-300',
            'time'    => 'text-red-600 dark:text-red-400',
            'label'   => 'Re-entry',
        ],
        'already_entered' => [
            'card'    => 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800',
            'bar'     => 'bg-red-400 dark:bg-red-600',
            'heading' => 'text-red-900 dark:text-red-100',
            'sub'     => 'text-red-700 dark:text-red-300',
            'time'    => 'text-red-600 dark:text-red-400',
            'label'   => 'Already inside',
        ],
        'not_found' => [
            'card'    => 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800',
            'bar'     => 'bg-red-400 dark:bg-red-600',
            'heading' => 'text-red-900 dark:text-red-100',
            'sub'     => 'text-red-700 dark:text-red-300',
            'time'    => 'text-red-600 dark:text-red-400',
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
                class="relative flex items-start gap-3 rounded-xl border px-4 py-3 overflow-hidden {{ $s['card'] }}"
            >
                {{-- Progress bar --}}
                <div
                    class="absolute bottom-0 left-0 h-0.5 transition-all ease-linear {{ $s['bar'] }}"
                    :style="`width: ${progress}%; transition-duration: 1s`"
                ></div>

                {{-- Icon --}}
                <flux:icon name="{{ $logIcons[$entry['type']] ?? 'x-circle' }}" class="size-5 shrink-0 mt-0.5 {{ $s['sub'] }}" />

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate {{ $s['heading'] }}">{{ $s['label'] }}</p>
                    <p class="text-xs truncate mt-0.5 {{ $s['sub'] }}">{{ $entry['name'] }}</p>
                    @if(!empty($entry['entered_at']))
                        <p class="text-xs font-mono truncate mt-0.5 {{ $s['sub'] }} opacity-75">Entered {{ $entry['entered_at'] }}</p>
                    @endif
                </div>

                {{-- Time + dismiss --}}
                <div class="shrink-0 flex flex-col items-end gap-2">
                    <button
                        @click="dismiss()"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                        aria-label="Dismiss"
                    >
                        <flux:icon name="x-mark" class="size-3.5" />
                    </button>
                    <span class="text-xs font-mono {{ $s['time'] }}">{{ $entry['time'] }}</span>
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
