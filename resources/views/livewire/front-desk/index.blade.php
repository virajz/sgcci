<div class="flex h-screen overflow-hidden bg-zinc-100 dark:bg-zinc-900" x-data="qrScanner()" x-init="init()">

    {{-- ── PANEL 1: QR CAMERA SCANNER ─────────────────────────────────────── --}}
    <div class="flex flex-col w-1/3 border-e border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:icon name="camera" class="size-5 text-zinc-500" />
            <flux:heading size="sm" class="font-semibold">QR Scanner</flux:heading>
        </div>

        <div class="relative flex-1 flex flex-col items-center justify-center p-4 gap-4">
            {{-- Camera preview --}}
            <div class="relative w-full aspect-square max-w-xs rounded-xl overflow-hidden bg-zinc-900">
                <video id="qr-video" class="w-full h-full object-cover" playsinline></video>
                {{-- Scanning frame overlay --}}
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-48 h-48 border-2 border-white/60 rounded-xl relative">
                        <span class="absolute -top-px -left-px w-6 h-6 border-t-4 border-l-4 border-blue-400 rounded-tl-lg"></span>
                        <span class="absolute -top-px -right-px w-6 h-6 border-t-4 border-r-4 border-blue-400 rounded-tr-lg"></span>
                        <span class="absolute -bottom-px -left-px w-6 h-6 border-b-4 border-l-4 border-blue-400 rounded-bl-lg"></span>
                        <span class="absolute -bottom-px -right-px w-6 h-6 border-b-4 border-r-4 border-blue-400 rounded-br-lg"></span>
                    </div>
                </div>
                {{-- Status overlay --}}
                <div x-show="!cameraActive" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-zinc-900/80">
                    <flux:icon name="camera" class="size-10 text-zinc-400" />
                    <span class="text-sm text-zinc-400">Camera inactive</span>
                </div>
            </div>

            <div class="text-center space-y-3 w-full max-w-xs">
                <flux:text class="text-xs text-zinc-500">Point camera at a visitor QR code to auto-search</flux:text>

                <template x-if="!cameraActive">
                    <flux:button variant="primary" size="sm" @click="startCamera()" class="w-full" icon="camera">
                        Start Camera
                    </flux:button>
                </template>
                <template x-if="cameraActive">
                    <flux:button variant="ghost" size="sm" @click="stopCamera()" class="w-full" icon="x-mark">
                        Stop Camera
                    </flux:button>
                </template>

                <div x-show="lastScanned" class="flex items-center gap-2 px-3 py-2 bg-green-50 dark:bg-green-950/30 rounded-lg">
                    <flux:icon name="check-circle" class="size-4 text-green-600 dark:text-green-400 shrink-0" />
                    <span class="text-xs font-mono text-green-700 dark:text-green-300 truncate" x-text="lastScanned"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── PANEL 2: SEARCH / LOOKUP ────────────────────────────────────────── --}}
    <div class="flex flex-col w-1/3 border-e border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:icon name="magnifying-glass" class="size-5 text-zinc-500" />
            <flux:heading size="sm" class="font-semibold">Visitor Lookup</flux:heading>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <form wire:submit="lookup" class="flex gap-2">
                <flux:field class="flex-1">
                    <flux:input
                        id="lookup-input"
                        wire:model="lookupCode"
                        placeholder="VIS-ABCDEF"
                        autofocus
                        clearable
                    />
                    <flux:error name="lookupCode" />
                </flux:field>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="lookup">
                    <span wire:loading.remove wire:target="lookup">Search</span>
                    <span wire:loading wire:target="lookup">...</span>
                </flux:button>
            </form>

            @if ($lookupPerformed)
                @if ($foundVisitor)
                    {{-- Primary visitor --}}
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-900/50 flex items-center gap-3">
                            <div class="flex items-center justify-center text-xs font-bold text-blue-700 bg-blue-100 rounded-full size-9 dark:bg-blue-900/50 dark:text-blue-300 shrink-0">
                                {{ mb_strtoupper(mb_substr($foundVisitor['name'], 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm truncate">{{ $foundVisitor['name'] }}</p>
                                <p class="text-xs text-zinc-500 font-mono">{{ $foundVisitor['phone_number'] }}</p>
                            </div>
                            <flux:badge color="{{ $foundVisitor['status_color'] }}" size="sm" class="shrink-0">
                                {{ $foundVisitor['status_label'] }}
                            </flux:badge>
                        </div>

                        @if ($foundVisitor['company_name'])
                            <div class="px-3 py-1.5 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <flux:icon name="building-office-2" class="size-3.5 shrink-0" />
                                <span class="truncate">{{ $foundVisitor['company_name'] }}{{ $foundVisitor['designation'] ? ' — '.$foundVisitor['designation'] : '' }}</span>
                            </div>
                        @endif

                        <div class="px-3 py-1.5 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                            <flux:icon name="map-pin" class="size-3.5 shrink-0" />
                            <span>{{ $foundVisitor['city'] }}, {{ $foundVisitor['state'] }}</span>
                        </div>

                    </div>

                    {{-- Additional persons --}}
                    @if (! empty($foundVisitor['additional_persons']))
                        <div class="space-y-2">
                            <flux:text class="text-xs text-zinc-500 font-medium uppercase tracking-wide">Additional Persons</flux:text>

                            @foreach ($foundVisitor['additional_persons'] as $index => $person)
                                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden" wire:key="person-result-{{ $index }}">
                                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900/50 flex items-center gap-3">
                                        <div class="flex items-center justify-center text-xs font-bold text-purple-700 bg-purple-100 rounded-full size-9 dark:bg-purple-900/50 dark:text-purple-300 shrink-0">
                                            {{ mb_strtoupper(mb_substr($person['name'], 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-sm truncate">{{ $person['name'] }}</p>
                                            @if (! empty($person['phone_number']))
                                                <p class="text-xs text-zinc-500 font-mono">{{ $person['phone_number'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <flux:button
                        variant="primary"
                        size="sm"
                        icon="printer"
                        class="w-full"
                        href="{{ route('front-desk.visitor.badge.print', $foundVisitor['registration_code']) }}"
                        target="_blank"
                    >
                        Print All Badges
                    </flux:button>

                    <flux:button variant="ghost" size="sm" wire:click="resetLookup" icon="arrow-left" class="w-full">
                        Search Again
                    </flux:button>
                @else
                    <flux:callout variant="danger" icon="exclamation-circle">
                        <flux:callout.heading>Not found</flux:callout.heading>
                        <flux:callout.text>No visitor with code <span class="font-mono font-semibold">{{ strtoupper($lookupCode) }}</span>.</flux:callout.text>
                    </flux:callout>
                @endif
            @endif
        </div>
    </div>

    {{-- ── PANEL 3: ADD WALK-IN VISITOR ───────────────────────────────────── --}}
    <div class="flex flex-col w-1/3 bg-white dark:bg-zinc-800">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:icon name="user-plus" class="size-5 text-zinc-500" />
            <flux:heading size="sm" class="font-semibold">Add Walk-in Visitor</flux:heading>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            @if ($addSuccess)
                <div class="flex flex-col items-center text-center gap-4 py-8">
                    <div class="flex items-center justify-center text-green-600 rounded-full size-14 bg-green-50 dark:bg-green-950/30 dark:text-green-400">
                        <flux:icon name="check-circle" class="size-7" />
                    </div>
                    <div>
                        <flux:heading size="sm">Registered!</flux:heading>
                        <flux:text class="text-zinc-500 text-sm mt-1">
                            Code: <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $addedRegistrationCode }}</span>
                        </flux:text>
                    </div>
                    <div class="flex flex-col gap-2 w-full">
                        <flux:button
                            variant="primary"
                            size="sm"
                            icon="printer"
                            href="{{ route('front-desk.visitor.badge.print', $addedRegistrationCode) }}"
                            target="_blank"
                        >
                            Print Badge
                        </flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="startNewRegistration" icon="user-plus">
                            Register Another
                        </flux:button>
                    </div>
                </div>
            @else
                <form wire:submit="registerWalkIn" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label class="text-xs">Phone <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="phoneNumber" type="tel" placeholder="9876543210" size="sm" />
                            <flux:error name="phoneNumber" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="text-xs">Full Name <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="name" placeholder="Full name" size="sm" />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label class="text-xs">State <span class="text-red-500">*</span></flux:label>
                            <flux:select variant="listbox" searchable wire:model.live="state" placeholder="State" size="sm">
                                @foreach ($states as $stateOption)
                                    <flux:select.option value="{{ $stateOption }}">{{ $stateOption }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="state" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="text-xs">City <span class="text-red-500">*</span></flux:label>
                            <flux:select variant="listbox" searchable wire:model="city" placeholder="City" size="sm">
                                @foreach ($cities as $cityOption)
                                    <flux:select.option value="{{ $cityOption }}">{{ $cityOption }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="city" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label class="text-xs">Company</flux:label>
                            <flux:input wire:model="companyName" placeholder="Optional" size="sm" />
                            <flux:error name="companyName" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="text-xs">Designation</flux:label>
                            <flux:input wire:model="designation" placeholder="Optional" size="sm" />
                            <flux:error name="designation" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label class="text-xs">Segment</flux:label>
                            <flux:select variant="listbox" wire:model="segment" placeholder="Segment" size="sm">
                                <flux:select.option value="Business">Business</flux:select.option>
                                <flux:select.option value="Job (Working Professional)">Working Professional</flux:select.option>
                                <flux:select.option value="Student">Student</flux:select.option>
                                <flux:select.option value="Housewife">Housewife</flux:select.option>
                                <flux:select.option value="Other">Other</flux:select.option>
                            </flux:select>
                            <flux:error name="segment" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="text-xs">Email</flux:label>
                            <flux:input wire:model="email" type="email" placeholder="Optional" size="sm" />
                            <flux:error name="email" />
                        </flux:field>
                    </div>

                    {{-- Additional Persons --}}
                    @if (count($additionalPersons) > 0)
                        <flux:separator />

                        @foreach ($additionalPersons as $index => $person)
                            <div class="flex items-start gap-2 p-2 border rounded-lg border-zinc-200 dark:border-zinc-700" wire:key="add-person-{{ $index }}">
                                <span class="flex items-center justify-center mt-1 text-xs font-semibold rounded-full size-6 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 shrink-0">{{ $index + 2 }}</span>
                                <div class="flex-1 grid grid-cols-2 gap-2">
                                    <div>
                                        <flux:input wire:model="additionalPersons.{{ $index }}.name" placeholder="Name" size="sm" />
                                        <flux:error name="additionalPersons.{{ $index }}.name" />
                                    </div>
                                    <div>
                                        <flux:input wire:model="additionalPersons.{{ $index }}.phone_number" type="tel" placeholder="Phone" size="sm" />
                                        <flux:error name="additionalPersons.{{ $index }}.phone_number" />
                                    </div>
                                </div>
                                <flux:button wire:click="removePerson({{ $index }})" variant="ghost" size="sm" icon="trash" class="mt-0.5 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 shrink-0" />
                            </div>
                        @endforeach
                    @endif

                    <flux:button wire:click="addPerson" variant="ghost" size="sm" icon="plus" class="w-full border border-dashed border-zinc-300 dark:border-zinc-700">
                        Add Another Person
                    </flux:button>

                    <flux:button type="submit" variant="primary" size="sm" class="w-full" wire:loading.attr="disabled" wire:target="registerWalkIn" icon="check">
                        <span wire:loading.remove wire:target="registerWalkIn">Register Visitor</span>
                        <span wire:loading wire:target="registerWalkIn">Registering...</span>
                    </flux:button>
                </form>
            @endif
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
        },

        async startCamera() {
            const hasBarcodeDetector = 'BarcodeDetector' in window;
            console.log('[QR] BarcodeDetector available:', hasBarcodeDetector);
            console.log('[QR] jsQR available:', typeof jsQR !== 'undefined');
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                const video = document.getElementById('qr-video');
                video.srcObject = this.stream;
                await video.play();
                this.cameraActive = true;
                console.log('[QR] Camera started, video size:', video.videoWidth, 'x', video.videoHeight);
                if (hasBarcodeDetector) {
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
            console.log('[QR] Camera stopped');
        },

        handleDetected(raw) {
            console.log('[QR] Raw data:', raw);
            const match = raw.match(/VIS-[A-Z0-9]+/i);
            const extracted = match ? match[0].toUpperCase() : raw.toUpperCase();
            console.log('[QR] Extracted:', extracted, '| previous:', this.lastScanned);
            if (extracted !== this.lastScanned) {
                this.lastScanned = extracted;
                console.log('[QR] Triggering lookup for:', extracted);
                @this.setLookupCode(extracted);
            }
        },

        // Native BarcodeDetector (Chrome 83+) — much more robust
        async scanLoopNative(video) {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            let attempt = 0;
            this.scanInterval = setInterval(async () => {
                if (!this.cameraActive || video.readyState !== video.HAVE_ENOUGH_DATA) return;
                attempt++;
                try {
                    const barcodes = await detector.detect(video);
                    if (attempt % 25 === 0) {
                        console.log('[QR] Native scan attempt', attempt, '| found:', barcodes.length);
                    }
                    if (barcodes.length > 0) {
                        this.handleDetected(barcodes[0].rawValue);
                    }
                } catch (e) {
                    console.error('[QR] BarcodeDetector error:', e);
                }
            }, 200);
        },

        // jsQR fallback
        scanLoopJsQR(video) {
            let attempt = 0;
            const tick = (timestamp) => {
                if (!this.cameraActive) return;
                this.animationFrame = requestAnimationFrame(tick);
                if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
                attempt++;
                this.canvas.width = video.videoWidth;
                this.canvas.height = video.videoHeight;
                this.ctx.drawImage(video, 0, 0, this.canvas.width, this.canvas.height);
                const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                if (attempt % 25 === 0) {
                    console.log('[QR] jsQR attempt', attempt, '| canvas:', this.canvas.width, 'x', this.canvas.height);
                }
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'attemptBoth'
                });
                if (code && code.data) {
                    this.handleDetected(code.data);
                }
            };
            this.animationFrame = requestAnimationFrame(tick);
        },
    }));
</script>
@endscript
