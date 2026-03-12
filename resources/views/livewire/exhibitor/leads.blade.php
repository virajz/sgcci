<div class="space-y-6">
    <div>
        <flux:heading size="xl">Leads</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
            Scan visitor QR codes to capture leads, or browse your saved leads list.
        </flux:text>
    </div>

    <flux:tabs wire:model.live="activeTab">
        <flux:tab name="capture" icon="qr-code">Capture</flux:tab>
        <flux:tab name="list" icon="list-bullet">List</flux:tab>
        <flux:tab name="members" icon="identification">Members</flux:tab>
        <flux:tab name="whatsapp" icon="chat-bubble-left-ellipsis">WhatsApp Inquiries</flux:tab>
    </flux:tabs>

    {{-- ── CAPTURE TAB ────────────────────────────────────────────────────────── --}}
    @if ($activeTab === 'capture')
        <div class="flex flex-col md:flex-row gap-4" x-data="leadsQrScanner()" x-init="init()">

            {{-- Left: Camera + Search --}}
            <div class="md:w-80 md:shrink-0 space-y-3">
                <flux:card class="p-0 overflow-hidden">
                    {{-- Camera --}}
                    <div
                        class="relative w-full overflow-hidden bg-zinc-900 transition-all duration-300"
                        :class="cameraActive ? 'aspect-square' : 'h-0'"
                    >
                        <video id="leads-qr-video" class="w-full h-full object-cover" playsinline></video>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-48 h-48 relative">
                                <span class="absolute -top-px -left-px w-8 h-8 border-t-4 border-l-4 border-white/80 rounded-tl-lg"></span>
                                <span class="absolute -top-px -right-px w-8 h-8 border-t-4 border-r-4 border-white/80 rounded-tr-lg"></span>
                                <span class="absolute -bottom-px -left-px w-8 h-8 border-b-4 border-l-4 border-white/80 rounded-bl-lg"></span>
                                <span class="absolute -bottom-px -right-px w-8 h-8 border-b-4 border-r-4 border-white/80 rounded-br-lg"></span>
                            </div>
                        </div>
                        <div x-show="lastScanned" x-transition class="absolute bottom-3 left-3 right-3 flex items-center gap-2 px-3 py-2 bg-black/60 rounded-lg backdrop-blur-sm">
                            <flux:icon name="check-circle" class="size-4 text-green-400 shrink-0" />
                            <span class="text-xs font-mono text-white truncate" x-text="lastScanned"></span>
                        </div>
                    </div>

                    <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
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

                    {{-- Search --}}
                    <div class="p-3">
                        <form wire:submit="lookup" class="flex gap-2">
                            <flux:field class="flex-1">
                                <flux:input
                                    wire:model="lookupCode"
                                    placeholder="Code, phone, or name…"
                                    clearable
                                    autofocus
                                />
                                <flux:error name="lookupCode" />
                            </flux:field>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="lookup">
                                <span wire:loading.remove wire:target="lookup">Search</span>
                                <span wire:loading wire:target="lookup">…</span>
                            </flux:button>
                        </form>
                    </div>
                </flux:card>
            </div>

            {{-- Right: Results --}}
            <div class="flex-1 space-y-3">
                @if ($lookupPerformed)
                    @if ($foundMember)

                        {{-- Member card --}}
                        <div class="rounded-xl border overflow-hidden border-indigo-300 dark:border-indigo-700">
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 flex items-center gap-3">
                                <div class="flex items-center justify-center size-12 rounded-full shrink-0 font-bold text-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300">
                                    {{ mb_strtoupper(mb_substr($foundMember['name'], 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-base truncate">{{ $foundMember['name'] }}</p>
                                    @if ($foundMember['phone'])
                                        <p class="text-sm text-zinc-500 font-mono">{{ $foundMember['phone'] }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1">
                                    @if ($foundMember['is_lead'])
                                        <flux:badge color="lime" size="sm" icon="check">Lead</flux:badge>
                                    @endif
                                    <flux:badge color="indigo" size="sm">
                                        {{ $foundMember['member_type'] === 'committee' ? 'Organizer' : 'SGCCI Member' }}
                                    </flux:badge>
                                </div>
                            </div>

                            @if ($foundMember['post'])
                                <div class="px-4 py-2 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    <flux:icon name="briefcase" class="size-4 shrink-0" />
                                    <span class="truncate">{{ $foundMember['post'] }}</span>
                                </div>
                            @endif

                            <div class="px-4 py-2 border-t border-zinc-100 dark:border-zinc-700/50 flex items-center justify-end text-sm">
                                <span class="font-mono text-xs text-zinc-400">{{ $foundMember['membership_number'] }}</span>
                            </div>
                        </div>

                        <flux:button
                            wire:click="markAsMemberLead"
                            wire:loading.attr="disabled"
                            wire:target="markAsMemberLead"
                            variant="primary"
                            icon="bookmark"
                            class="w-full !py-4 !text-base"
                            :disabled="$foundMember['is_lead']"
                        >
                            <span wire:loading.remove wire:target="markAsMemberLead">
                                {{ $foundMember['is_lead'] ? 'Already Saved' : 'Save as Lead' }}
                            </span>
                            <span wire:loading wire:target="markAsMemberLead">Saving…</span>
                        </flux:button>

                        <flux:button variant="ghost" icon="arrow-left" wire:click="resetLookup" class="w-full">
                            Search Again
                        </flux:button>

                    @elseif ($foundVisitor)

                        {{-- Visitor card --}}
                        <div class="rounded-xl border overflow-hidden
                            {{ $foundVisitor['scanned_person_index'] === null ? 'border-blue-300 dark:border-blue-700' : 'border-zinc-200 dark:border-zinc-700' }}">
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 flex items-center gap-3">
                                <div class="flex items-center justify-center size-12 rounded-full shrink-0 font-bold text-lg bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                                    {{ mb_strtoupper(mb_substr($foundVisitor['name'], 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-base truncate">{{ $foundVisitor['name'] }}</p>
                                    <p class="text-sm text-zinc-500 font-mono">{{ $foundVisitor['phone_number'] }}</p>
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1">
                                    @if ($foundVisitor['is_lead'])
                                        <flux:badge color="lime" size="sm" icon="check">Lead</flux:badge>
                                    @endif
                                </div>
                            </div>

                            @if ($foundVisitor['company_name'])
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

                        {{-- Additional persons --}}
                        @if (!empty($foundVisitor['additional_persons']))
                            <div class="space-y-2">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide px-1">
                                    Additional Persons ({{ count($foundVisitor['additional_persons']) }})
                                </p>
                                @foreach ($foundVisitor['additional_persons'] as $index => $person)
                                    @php $isScanned = $foundVisitor['scanned_person_index'] === $index; @endphp
                                    <div wire:key="lead-person-{{ $index }}"
                                        class="rounded-xl border px-3 py-2.5 flex items-center gap-3 {{ $isScanned ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800' }}"
                                    >
                                        <div class="flex items-center justify-center size-9 rounded-full shrink-0 text-sm font-bold {{ $isScanned ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' }}">
                                            {{ mb_strtoupper(mb_substr($person['name'], 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold truncate">{{ $person['name'] }}</p>
                                            @if (!empty($person['phone_number']))
                                                <p class="text-xs text-zinc-500 font-mono">{{ $person['phone_number'] }}</p>
                                            @endif
                                        </div>
                                        @if ($person['is_lead'])
                                            <flux:badge color="lime" size="sm" icon="check">Lead</flux:badge>
                                        @elseif ($isScanned)
                                            <flux:badge color="blue" size="sm">Scanned</flux:badge>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <flux:button
                            wire:click="markAsLead"
                            wire:loading.attr="disabled"
                            wire:target="markAsLead"
                            variant="primary"
                            icon="bookmark"
                            class="w-full !py-4 !text-base"
                        >
                            <span wire:loading.remove wire:target="markAsLead">Save as Lead</span>
                            <span wire:loading wire:target="markAsLead">Saving…</span>
                        </flux:button>

                        <flux:button variant="ghost" icon="arrow-left" wire:click="resetLookup" class="w-full">
                            Search Again
                        </flux:button>

                    @elseif (!empty($matchedVisitors))

                        <flux:callout variant="warning" icon="users">
                            <flux:callout.heading>{{ count($matchedVisitors) }} visitors found</flux:callout.heading>
                            <flux:callout.text>Tap the correct visitor below.</flux:callout.text>
                        </flux:callout>

                        <div class="space-y-2">
                            @foreach ($matchedVisitors as $index => $match)
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
                                            @if ($match['company_name'])
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
                        <flux:icon name="qr-code" class="size-12 text-zinc-300 dark:text-zinc-700" />
                        <flux:text class="text-zinc-500">Scan a visitor QR code or search above to capture a lead.</flux:text>
                    </div>

                @endif
            </div>
        </div>

        @script
        <script>
            Alpine.data('leadsQrScanner', () => ({
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
                            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
                        });
                        const video = document.getElementById('leads-qr-video');
                        video.srcObject = this.stream;
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
    @endif

    {{-- ── MEMBERS TAB ─────────────────────────────────────────────────────────── --}}
    @if ($activeTab === 'members')
        <flux:card class="space-y-4">
            @if ($memberLeads->isEmpty())
                <div class="py-12 text-center">
                    <flux:icon name="identification" class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mb-1">No member leads yet</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 mb-4">
                        Scan an organizer or SGCCI member badge QR code to save them as a lead.
                    </flux:text>
                    <flux:button variant="primary" icon="qr-code" wire:click="$set('activeTab', 'capture')">
                        Scan a Badge
                    </flux:button>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Membership No.</flux:table.column>
                        <flux:table.column>Captured</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($memberLeads as $lead)
                            <flux:table.row wire:key="member-lead-{{ $lead->id }}">
                                <flux:table.cell>
                                    <flux:text class="font-medium">{{ $lead->member_name }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm font-mono">{{ $lead->member_phone ?? '—' }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge color="{{ $lead->member_type === 'committee' ? 'indigo' : 'blue' }}" size="sm">
                                        {{ $lead->member_type === 'committee' ? 'Organizer' : 'SGCCI Member' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" color="zinc" class="font-mono">
                                        {{ $lead->membership_number }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $lead->captured_at->format('d M Y, h:i A') }}
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @endif

    {{-- ── WHATSAPP TAB ─────────────────────────────────────────────────────────── --}}
    @if ($activeTab === 'whatsapp')
        <flux:card class="space-y-4">
            @if ($whatsAppInquiries->isEmpty())
                <div class="py-12 text-center">
                    <flux:icon name="chat-bubble-left-ellipsis" class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mb-1">No WhatsApp inquiries yet</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        When visitors scan your QR code and message you, they'll appear here.
                    </flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>Received</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($whatsAppInquiries as $inquiry)
                            <flux:table.row wire:key="inquiry-{{ $inquiry->id }}">
                                <flux:table.cell>
                                    <flux:text class="font-medium">{{ $inquiry->name ?? '—' }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm font-mono">{{ $inquiry->phone_number }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $inquiry->received_at->format('d M Y, h:i A') }}
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @endif

    {{-- ── LIST TAB ────────────────────────────────────────────────────────────── --}}
    @if ($activeTab === 'list')
        @if (!$leads->isEmpty())
            <div class="flex items-center justify-between">
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $leads->count() }} lead{{ $leads->count() === 1 ? '' : 's' }} captured</flux:text>
                <flux:button
                    wire:click="exportLeads"
                    wire:loading.attr="disabled"
                    wire:target="exportLeads"
                    variant="ghost"
                    icon="arrow-down-tray"
                    size="sm"
                >
                    Export CSV
                </flux:button>
            </div>
        @endif
        <flux:card class="space-y-4">
            @if ($leads->isEmpty())
                <div class="py-12 text-center">
                    <flux:icon.bookmark class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mb-1">No leads yet</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 mb-4">
                        Switch to the Capture tab to scan visitors and save them as leads.
                    </flux:text>
                    <flux:button variant="primary" icon="qr-code" wire:click="$set('activeTab', 'capture')">
                        Capture a Lead
                    </flux:button>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>Company</flux:table.column>
                        <flux:table.column>Location</flux:table.column>
                        <flux:table.column>Pass Code</flux:table.column>
                        <flux:table.column>Captured</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($leads as $lead)
                            @php
                                $persons = is_array($lead->visitor->additional_persons) ? $lead->visitor->additional_persons : [];
                                $person  = $lead->person_index !== null ? ($persons[$lead->person_index] ?? null) : null;
                                $name    = $person ? $person['name'] : $lead->visitor->name;
                                $phone   = $person ? ($person['phone_number'] ?? '—') : $lead->visitor->phone_number;
                            @endphp
                            <flux:table.row wire:key="lead-{{ $lead->id }}">
                                <flux:table.cell>
                                    <flux:text class="font-medium">{{ $name }}</flux:text>
                                    @if (!$person && $lead->visitor->designation)
                                        <flux:text class="text-xs text-zinc-400">{{ $lead->visitor->designation }}</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm font-mono">{{ $phone }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $lead->visitor->company_name ?? '—' }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $lead->visitor->city }}, {{ $lead->visitor->state }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" color="zinc" class="font-mono">
                                        {{ $lead->visitor->registration_code }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $lead->captured_at->format('d M Y, h:i A') }}
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @endif
</div>
