<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="h-screen flex flex-col overflow-hidden bg-zinc-100 dark:bg-zinc-900">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

    {{-- Top bar --}}
    <header class="flex items-center justify-between px-4 h-11 shrink-0 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <div class="flex items-center gap-2">
            <flux:icon name="shield-check" class="size-4 text-blue-500 dark:text-blue-400" />
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 tracking-tight">Security</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                Sign out
            </button>
        </form>
    </header>

    <div class="flex-1 overflow-hidden">
        {{ $slot }}
    </div>

    @persist('toast')
        <flux:toast />
    @endpersist

    {{-- Scan result modal --}}
    <div
        x-data="scanResultModal()"
        x-show="visible"
        x-transition:enter="transition duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition duration-300"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="dismiss()"
        class="fixed inset-0 z-50 flex items-center justify-center p-8 bg-black/50 backdrop-blur-sm cursor-pointer"
        style="display: none"
    >
        <div class="text-center text-white select-none pointer-events-none rounded-2xl px-12 py-10 w-[60vw] h-[60vh] flex flex-col items-center justify-center" :class="bgClass">
            <div x-html="iconHtml" class="flex justify-center mb-4"></div>
            <p class="text-4xl font-black tracking-tight mb-2" x-text="label"></p>
            <p class="text-xl font-medium opacity-90" x-text="name"></p>
            <p class="text-base opacity-80 mt-1" x-show="sub" x-text="sub"></p>
            <p class="text-sm font-mono opacity-70 mt-1" x-show="enteredAt" x-text="'Entered ' + enteredAt"></p>
            {{-- Progress bar --}}
            <div class="mt-8 w-full h-1.5 rounded-full bg-white/30">
                <div class="h-full rounded-full bg-white transition-all ease-linear" :style="`width: ${progress}%; transition-duration: 1s`"></div>
            </div>
        </div>
    </div>

    <script>
        const scanResultIcons = {
            entered: `<svg xmlns="http://www.w3.org/2000/svg" class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" /></svg>`,
            re_entered: `<svg xmlns="http://www.w3.org/2000/svg" class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>`,
            already_entered: `<svg xmlns="http://www.w3.org/2000/svg" class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>`,
            not_found: `<svg xmlns="http://www.w3.org/2000/svg" class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`,
        };

        const scanResultLabels = {
            entered: 'Entry allowed',
            re_entered: 'Re-entry',
            already_entered: 'Already inside',
            not_found: 'Not found',
        };

        const scanResultBg = {
            entered: 'bg-green-500',
            re_entered: 'bg-red-500',
            already_entered: 'bg-red-500',
            not_found: 'bg-red-500',
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('scanResultModal', () => ({
                visible: false,
                label: '',
                name: '',
                sub: '',
                enteredAt: '',
                bgClass: '',
                iconHtml: '',
                progress: 100,
                timer: null,
                duration: 4,

                init() {
                    window.addEventListener('scan-result', (e) => {
                        this.show(e.detail.entry ?? e.detail);
                    });
                },

                show(entry) {
                    clearInterval(this.timer);
                    const isMember = !!entry.is_member;
                    const bgKey = (isMember && ['already_entered', 're_entered'].includes(entry.type)) ? 'entered' : entry.type;
                    this.label = isMember && ['already_entered', 're_entered'].includes(entry.type) ? 'Entry allowed' : (scanResultLabels[entry.type] ?? entry.type);
                    this.name = entry.name;
                    this.sub = entry.sub ?? '';
                    this.enteredAt = (!isMember && entry.entered_at) ? entry.entered_at : '';
                    this.bgClass = scanResultBg[bgKey] ?? 'bg-red-500';
                    this.iconHtml = scanResultIcons[bgKey] ?? scanResultIcons.not_found;
                    this.progress = 100;
                    this.visible = true;

                    const step = 100 / this.duration;
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
                },
            }));
        });
    </script>

    @fluxScripts
</body>

</html>
