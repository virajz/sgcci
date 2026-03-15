<div class="min-h-screen bg-zinc-950 flex flex-col items-center p-4 gap-6" x-data="cameraScanner()" x-init="init()">

    {{-- Square camera box --}}
    <div class="relative w-full max-w-sm aspect-square rounded-2xl overflow-hidden bg-black mt-4 shrink-0">
        <video id="cam-video" class="absolute inset-0 w-full h-full object-cover" autoplay muted playsinline></video>
    </div>

    {{-- Result --}}
    <div class="w-full max-w-sm">
        <div x-show="result" class="bg-white rounded-2xl px-6 py-5 text-center" style="display:none">
            <div class="text-4xl font-black tracking-tight font-mono" x-text="result && result.found ? result.code : 'Not found'" :class="result && !result.found ? 'text-red-500' : 'text-zinc-900'"></div>
        </div>
    </div>

</div>

@script
<script>
    Alpine.data('cameraScanner', () => ({
        result: null,
        timer: null,
        lastCode: '',
        cooldownTimer: null,
        stream: null,
        animationFrame: null,
        scanInterval: null,
        canvas: null,
        ctx: null,

        init() {
            this.canvas = document.createElement('canvas');
            this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
            this.startCamera();

            window.addEventListener('code-result', (e) => this.showResult(e.detail));
        },

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                const video = document.getElementById('cam-video');
                video.srcObject = this.stream;
                await new Promise(resolve => {
                    if (video.readyState >= 1) { resolve(); return; }
                    video.addEventListener('loadedmetadata', resolve, { once: true });
                });
                await video.play();
                // Always use canvas-based loop so we can apply contrast+grayscale
                // which helps with coloured QR borders (e.g. dark blue)
                'BarcodeDetector' in window ? this.scanLoopNative(video) : this.scanLoopJsQR(video);
            } catch (err) {
                console.error('[Camera] Error:', err);
            }
        },

        // Draw video to canvas with contrast+grayscale filter to normalise coloured borders
        drawFiltered(video) {
            this.canvas.width = video.videoWidth;
            this.canvas.height = video.videoHeight;
            this.ctx.filter = 'grayscale(1) contrast(1.8)';
            this.ctx.drawImage(video, 0, 0, this.canvas.width, this.canvas.height);
            this.ctx.filter = 'none';
        },

        handleDetected(raw) {
            if (raw === this.lastCode) return;
            this.lastCode = raw;
            @this.setCode(raw);

            // Allow the same code again after 30s
            clearTimeout(this.cooldownTimer);
            this.cooldownTimer = setTimeout(() => { this.lastCode = ''; }, 30000);
        },

        async scanLoopNative(video) {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            this.scanInterval = setInterval(async () => {
                if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
                try {
                    // First try raw video (fast path)
                    let codes = await detector.detect(video);
                    if (codes.length === 0) {
                        // Retry with filtered canvas for coloured borders
                        this.drawFiltered(video);
                        codes = await detector.detect(this.canvas);
                    }
                    if (codes.length > 0) this.handleDetected(codes[0].rawValue);
                } catch (e) {}
            }, 250);
        },

        scanLoopJsQR(video) {
            const tick = () => {
                this.animationFrame = requestAnimationFrame(tick);
                if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
                this.drawFiltered(video);
                const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'attemptBoth' });
                if (code?.data) this.handleDetected(code.data);
            };
            this.animationFrame = requestAnimationFrame(tick);
        },

        showResult(detail) {
            clearTimeout(this.timer);
            this.result = detail;
            this.timer = setTimeout(() => { this.result = null; }, 30000);
        },
    }));
</script>
@endscript
