<div class="flex flex-col md:flex-row h-full overflow-hidden" x-data="qrScanner()" x-init="init()">

    {{-- ── LEFT PANEL: CAMERA + SEARCH ─────────────────────────────────────── --}}
    <div class="md:w-96 md:shrink-0 md:h-full md:flex md:flex-col md:border-e md:border-zinc-200 md:dark:border-zinc-700 bg-white dark:bg-zinc-800">

        {{-- Camera --}}
        <div class="shrink-0 border-b border-zinc-200 dark:border-zinc-700">
            <div
                class="relative w-full overflow-hidden bg-zinc-900 transition-all duration-300"
                :class="cameraActive ? 'aspect-square md:aspect-auto md:h-72' : 'h-0'"
            >
                <video id="qr-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                {{-- Scanning frame overlay --}}
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-44 h-44 md:w-52 md:h-52 relative">
                        <span class="absolute -top-px -left-px w-8 h-8 border-t-4 border-l-4 border-white/80 rounded-tl-lg"></span>
                        <span class="absolute -top-px -right-px w-8 h-8 border-t-4 border-r-4 border-white/80 rounded-tr-lg"></span>
                        <span class="absolute -bottom-px -left-px w-8 h-8 border-b-4 border-l-4 border-white/80 rounded-bl-lg"></span>
                        <span class="absolute -bottom-px -right-px w-8 h-8 border-b-4 border-r-4 border-white/80 rounded-br-lg"></span>
                    </div>
                </div>
                {{-- Last scanned pill --}}
                <div x-show="lastScanned" x-transition class="absolute bottom-3 left-3 right-3 flex items-center gap-2 px-3 py-2 bg-black/60 rounded-lg backdrop-blur-sm">
                    <flux:icon name="check-circle" class="size-4 text-green-400 shrink-0" />
                    <span class="text-xs font-mono text-white truncate" x-text="lastScanned"></span>
                </div>
            </div>

            {{-- Camera toggle --}}
            <div class="p-3">
                <template x-if="!cameraActive">
                    <flux:button variant="primary" icon="camera" @click="startCamera()" class="w-full">
                        Start QR Camera
                    </flux:button>
                </template>
                <template x-if="cameraActive">
                    <flux:button variant="ghost" icon="x-mark" @click="stopCamera()" class="w-full">
                        Stop Camera
                    </flux:button>
                </template>
            </div>
        </div>

        {{-- Manual Search --}}
        <div class="shrink-0 p-3 border-b border-zinc-200 dark:border-zinc-700">
            <form wire:submit="lookup" class="flex gap-2">
                <flux:field class="flex-1">
                    <flux:input
                        wire:model="lookupCode"
                        placeholder="Code, phone, or name…"
                        clearable
                        autofocus
                        @refocus-search.window="$el.querySelector('input')?.focus()"
                    />
                    <flux:error name="lookupCode" />
                </flux:field>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="lookup">
                    <span wire:loading.remove wire:target="lookup">Search</span>
                    <span wire:loading wire:target="lookup">…</span>
                </flux:button>
            </form>
        </div>

        {{-- On mobile: results + log sit below search --}}
        <div class="md:hidden flex-1 overflow-y-auto">
            <div class="p-3 space-y-3">
                @include('livewire.security-desk.results')
            </div>
            @include('livewire.security-desk.log')
        </div>
    </div>

    {{-- ── RIGHT PANEL (desktop only) ──────────────────────────────────────── --}}
    <div class="hidden md:flex flex-col flex-1 overflow-hidden bg-zinc-50 dark:bg-zinc-900">

        {{-- Manual search results (shown above log when a search is active) --}}
        @if ($lookupPerformed)
            <div class="shrink-0 border-b border-zinc-200 dark:border-zinc-700 p-4 space-y-3 max-w-lg w-full mx-auto overflow-y-auto max-h-[60%]">
                @include('livewire.security-desk.results')
            </div>
        @endif

        {{-- Scan log --}}
        <div class="flex-1 overflow-y-auto">
            @include('livewire.security-desk.log')
        </div>
    </div>

</div>

@script
<script>
    Alpine.data('qrScanner', () => ({
        cameraActive: false,
        lastScanned: '',
        stream: null,
        animationFrame: null,
        scanInterval: null,
        canvas: null,
        ctx: null,

        init() {
            this.canvas = document.createElement('canvas');
            this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
            this.startCamera();
        },

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                const video = document.getElementById('qr-video');
                video.srcObject = this.stream;
                await new Promise(resolve => {
                    if (video.readyState >= 1) { resolve(); return; }
                    video.addEventListener('loadedmetadata', resolve, { once: true });
                });
                await video.play();
                this.cameraActive = true;
                if ('BarcodeDetector' in window) {
                    this.scanLoopNative(video);
                } else {
                    this.scanLoopJsQR(video);
                }
            } catch (err) {
                console.error('[QR] Camera error:', err);
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
                this.animationFrame = null;
            }
            if (this.scanInterval) {
                clearInterval(this.scanInterval);
                this.scanInterval = null;
            }
            this.cameraActive = false;
            this.lastScanned = '';
        },

        handleDetected(raw) {
            if (raw !== this.lastScanned) {
                this.lastScanned = raw;
                @this.setLookupCode(raw);
            }
        },

        async scanLoopNative(video) {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            this.scanInterval = setInterval(async () => {
                if (!this.cameraActive || video.readyState !== video.HAVE_ENOUGH_DATA) return;
                try {
                    const barcodes = await detector.detect(video);
                    if (barcodes.length > 0) this.handleDetected(barcodes[0].rawValue);
                } catch (e) {}
            }, 200);
        },

        scanLoopJsQR(video) {
            const tick = () => {
                if (!this.cameraActive) return;
                this.animationFrame = requestAnimationFrame(tick);
                if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
                this.canvas.width = video.videoWidth;
                this.canvas.height = video.videoHeight;
                this.ctx.drawImage(video, 0, 0, this.canvas.width, this.canvas.height);
                const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'attemptBoth' });
                if (code && code.data) this.handleDetected(code.data);
            };
            this.animationFrame = requestAnimationFrame(tick);
        },
    }));
</script>
@endscript
