@php
    $logoPreviewUrl = $logoUpload ? $logoUpload->temporaryUrl() : $existingLogoUrl;
    $passBackgroundPreviewUrl = $passBackgroundUpload ? $passBackgroundUpload->temporaryUrl() : $existingPassBackgroundUrl;
    $invitationBackgroundPreviewUrl = $invitationBackgroundUpload ? $invitationBackgroundUpload->temporaryUrl() : $existingInvitationBackgroundUrl;
@endphp

<div class="space-y-6">
    <flux:separator text="Branding & Visitor Pass" />

    {{-- Logo --}}
    <flux:field>
        <flux:label>Event Logo</flux:label>
        <flux:description>
            Shown alongside the SGCCI logo on every page for this event. Replaces the default Auto Expo logo.
        </flux:description>

        <flux:file-upload wire:model="logoUpload" accept="image/png,image/jpeg,image/svg+xml,image/webp">
            <flux:file-upload.dropzone
                heading="Drop logo here or click to browse"
                text="PNG, JPG, SVG or WebP up to 5MB"
            />
        </flux:file-upload>
        <flux:error name="logoUpload" />

        @if ($logoPreviewUrl)
            <div class="flex items-center gap-3 p-3 mt-3 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                <img src="{{ $logoPreviewUrl }}" alt="Event logo preview" class="object-contain w-16 h-16 p-1 bg-white rounded" />
                <div class="flex-1 text-sm text-zinc-600 dark:text-zinc-300">
                    {{ $logoUpload ? 'New logo selected — will save when you submit.' : 'Current event logo.' }}
                </div>
                @if ($existingLogoUrl && ! $logoUpload)
                    <flux:button size="sm" variant="ghost" type="button" wire:click="removeLogo" icon="trash" color="red">
                        Remove
                    </flux:button>
                @endif
            </div>
        @endif
    </flux:field>

    {{-- Pass background --}}
    <flux:field>
        <flux:label>Visitor Pass Background</flux:label>
        <flux:description>
            Upload the artwork visitors will receive as their pass. Then drag the QR square and the name marker on the preview to position them.
        </flux:description>

        <flux:file-upload wire:model="passBackgroundUpload" accept="image/png,image/jpeg,image/webp">
            <flux:file-upload.dropzone
                heading="Drop pass background here or click to browse"
                text="PNG, JPG or WebP, up to 10MB"
            />
        </flux:file-upload>
        <flux:error name="passBackgroundUpload" />

        @if ($passBackgroundPreviewUrl)
            <div
                wire:ignore
                wire:key="pass-bg-canvas-{{ $passBackgroundPreviewUrl }}"
                x-data="passLayoutEditor({
                    qrX: @entangle('passQrX'),
                    qrY: @entangle('passQrY'),
                    qrSize: @entangle('passQrSize'),
                    nameX: @entangle('passNameX'),
                    nameY: @entangle('passNameY'),
                    nameColor: @entangle('passNameColor'),
                })"
                class="mt-4 space-y-3"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs text-zinc-500" x-show="srcW && srcH">
                        Source: <span x-text="srcW"></span> × <span x-text="srcH"></span> px. Drag the blue QR square or the orange name pill to reposition.
                    </div>
                    @if ($existingPassBackgroundUrl && ! $passBackgroundUpload)
                        <flux:button size="sm" variant="ghost" type="button" wire:click="removePassBackground" icon="trash" color="red">
                            Remove background
                        </flux:button>
                    @endif
                </div>

                <div
                    x-ref="canvas"
                    class="relative inline-block max-w-full overflow-hidden border rounded-lg select-none border-zinc-300 dark:border-zinc-600"
                    style="touch-action: none;"
                >
                    <img
                        x-ref="bg"
                        src="{{ $passBackgroundPreviewUrl }}"
                        @load="measure()"
                        alt="Pass background"
                        class="block max-w-full h-auto max-h-[480px]"
                        draggable="false"
                    />

                    <div
                        x-show="qrSize > 0 && srcW > 0"
                        @pointerdown="startDrag('qr', $event)"
                        :style="`position:absolute; left:${qrX * scale}px; top:${qrY * scale}px; width:${qrSize * scale}px; height:${qrSize * scale}px; background:rgba(59,130,246,0.35); border:2px solid #2563eb; cursor:move;`"
                        class="flex items-center justify-center text-xs font-semibold text-white"
                    >
                        QR
                    </div>

                    <div
                        x-show="nameX !== null && nameY !== null && srcW > 0"
                        @pointerdown="startDrag('name', $event)"
                        :style="`position:absolute; left:${nameX * scale}px; top:${nameY * scale}px; transform:translate(-50%,-50%); padding:2px 12px; background:rgba(255,255,255,0.85); color:${nameColor || '#39318a'}; border:1px dashed ${nameColor || '#39318a'}; border-radius:6px; cursor:move; font-size:13px; font-weight:700; white-space:nowrap;`"
                    >
                        VISITOR NAME
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <flux:field>
                        <flux:label size="sm">QR X</flux:label>
                        <flux:input type="number" min="0" wire:model.live="passQrX" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">QR Y</flux:label>
                        <flux:input type="number" min="0" wire:model.live="passQrY" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">QR Size</flux:label>
                        <flux:input type="number" min="1" wire:model.live="passQrSize" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Name X</flux:label>
                        <flux:input type="number" min="0" wire:model.live="passNameX" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Name Y</flux:label>
                        <flux:input type="number" min="0" wire:model.live="passNameY" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label size="sm">Name Text Colour</flux:label>
                    <flux:description>Colour used to draw the visitor's name on the pass.</flux:description>
                    <flux:color-picker wire:model.live="passNameColor" format="hex" placeholder="#39318a" />
                </flux:field>
            </div>
        @endif

        <flux:error name="passQrX" />
        <flux:error name="passQrY" />
        <flux:error name="passQrSize" />
        <flux:error name="passNameX" />
        <flux:error name="passNameY" />
        <flux:error name="passNameColor" />
    </flux:field>

    {{-- Invitation pass --}}
    <flux:field>
        <flux:label>Invitation Pass Background</flux:label>
        <flux:description>
            Upload the artwork for exhibitor invitation passes. Then drag the stall number, company name and logo markers on the preview to position them.
        </flux:description>

        <flux:file-upload wire:model="invitationBackgroundUpload" accept="image/png,image/jpeg,image/webp">
            <flux:file-upload.dropzone
                heading="Drop invitation background here or click to browse"
                text="PNG, JPG or WebP, up to 10MB"
            />
        </flux:file-upload>
        <flux:error name="invitationBackgroundUpload" />

        @if ($invitationBackgroundPreviewUrl)
            <div
                wire:ignore
                wire:key="invitation-bg-canvas-{{ $invitationBackgroundPreviewUrl }}"
                x-data="invitationLayoutEditor({
                    stallX: @entangle('invitationStallX'),
                    stallY: @entangle('invitationStallY'),
                    companyX: @entangle('invitationCompanyX'),
                    companyY: @entangle('invitationCompanyY'),
                    logoX: @entangle('invitationLogoX'),
                    logoY: @entangle('invitationLogoY'),
                    logoSize: @entangle('invitationLogoSize'),
                    textColor: @entangle('invitationTextColor'),
                })"
                class="mt-4 space-y-3"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs text-zinc-500" x-show="srcW && srcH">
                        Source: <span x-text="srcW"></span> × <span x-text="srcH"></span> px. Drag the orange stall pill, the green company pill, or the blue logo square to reposition.
                    </div>
                    @if ($existingInvitationBackgroundUrl && ! $invitationBackgroundUpload)
                        <flux:button size="sm" variant="ghost" type="button" wire:click="removeInvitationBackground" icon="trash" color="red">
                            Remove background
                        </flux:button>
                    @endif
                </div>

                <div
                    x-ref="canvas"
                    class="relative inline-block max-w-full overflow-hidden border rounded-lg select-none border-zinc-300 dark:border-zinc-600"
                    style="touch-action: none;"
                >
                    <img
                        x-ref="bg"
                        src="{{ $invitationBackgroundPreviewUrl }}"
                        @load="measure()"
                        alt="Invitation background"
                        class="block max-w-full h-auto max-h-[480px]"
                        draggable="false"
                    />

                    <div
                        x-show="logoSize > 0 && srcW > 0"
                        @pointerdown="startDrag('logo', $event)"
                        :style="`position:absolute; left:${logoX * scale}px; top:${logoY * scale}px; width:${logoSize * scale}px; height:${logoSize * scale}px; background:rgba(59,130,246,0.35); border:2px solid #2563eb; cursor:move;`"
                        class="flex items-center justify-center text-xs font-semibold text-white"
                    >
                        LOGO
                    </div>

                    <div
                        x-show="stallX !== null && stallY !== null && srcW > 0"
                        @pointerdown="startDrag('stall', $event)"
                        :style="`position:absolute; left:${stallX * scale}px; top:${stallY * scale}px; transform:translate(-50%,-50%); padding:2px 12px; background:rgba(255,255,255,0.85); color:${textColor || '#39318a'}; border:1px dashed #ea580c; border-radius:6px; cursor:move; font-size:13px; font-weight:700; white-space:nowrap;`"
                    >
                        STALL NO
                    </div>

                    <div
                        x-show="companyX !== null && companyY !== null && srcW > 0"
                        @pointerdown="startDrag('company', $event)"
                        :style="`position:absolute; left:${companyX * scale}px; top:${companyY * scale}px; transform:translate(-50%,-50%); padding:2px 12px; background:rgba(255,255,255,0.85); color:${textColor || '#39318a'}; border:1px dashed #16a34a; border-radius:6px; cursor:move; font-size:13px; font-weight:700; white-space:nowrap;`"
                    >
                        COMPANY NAME
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <flux:field>
                        <flux:label size="sm">Stall X</flux:label>
                        <flux:input type="number" min="0" wire:model.live="invitationStallX" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Stall Y</flux:label>
                        <flux:input type="number" min="0" wire:model.live="invitationStallY" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Company X</flux:label>
                        <flux:input type="number" min="0" wire:model.live="invitationCompanyX" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Company Y</flux:label>
                        <flux:input type="number" min="0" wire:model.live="invitationCompanyY" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Logo X</flux:label>
                        <flux:input type="number" min="0" wire:model.live="invitationLogoX" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Logo Y</flux:label>
                        <flux:input type="number" min="0" wire:model.live="invitationLogoY" />
                    </flux:field>
                    <flux:field>
                        <flux:label size="sm">Logo Size</flux:label>
                        <flux:input type="number" min="1" wire:model.live="invitationLogoSize" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label size="sm">Text Colour</flux:label>
                    <flux:description>Colour used to draw the stall number and company name on the pass.</flux:description>
                    <flux:color-picker wire:model.live="invitationTextColor" format="hex" placeholder="#39318a" />
                </flux:field>
            </div>
        @endif

        <flux:error name="invitationStallX" />
        <flux:error name="invitationStallY" />
        <flux:error name="invitationCompanyX" />
        <flux:error name="invitationCompanyY" />
        <flux:error name="invitationLogoX" />
        <flux:error name="invitationLogoY" />
        <flux:error name="invitationLogoSize" />
        <flux:error name="invitationTextColor" />
    </flux:field>
</div>

@once
    <script>
        window.passLayoutEditor = function (initial) {
                return {
                    qrX: initial.qrX,
                    qrY: initial.qrY,
                    qrSize: initial.qrSize,
                    nameX: initial.nameX,
                    nameY: initial.nameY,
                    nameColor: initial.nameColor,
                    srcW: 0,
                    srcH: 0,
                    displayW: 0,
                    displayH: 0,
                    dragging: null,
                    pointerOffsetX: 0,
                    pointerOffsetY: 0,
                    boundMove: null,
                    boundUp: null,

                    init() {
                        this.boundMove = this.onMove.bind(this);
                        this.boundUp = this.onUp.bind(this);
                        window.addEventListener('resize', () => this.measure());
                        this.$nextTick(() => this.measure());
                    },

                    get scale() {
                        return this.srcW > 0 ? this.displayW / this.srcW : 1;
                    },

                    measure() {
                        const img = this.$refs.bg;
                        if (!img) return;
                        if (!img.complete || img.naturalWidth === 0) {
                            img.addEventListener('load', () => this.measure(), { once: true });
                            return;
                        }
                        this.srcW = img.naturalWidth;
                        this.srcH = img.naturalHeight;
                        this.displayW = img.clientWidth;
                        this.displayH = img.clientHeight;
                        this.seedDefaults();
                    },

                    seedDefaults() {
                        if (!this.srcW || !this.srcH) return;

                        if (!this.qrSize || this.qrSize < 1) {
                            const size = Math.round(Math.min(this.srcW, this.srcH) * 0.4);
                            this.qrSize = size;
                            this.qrX = Math.round((this.srcW - size) / 2);
                            this.qrY = Math.round((this.srcH - size) / 2);
                        }
                        if (this.nameX === null || this.nameX === undefined || this.nameX === '') {
                            this.nameX = Math.round(this.srcW / 2);
                        }
                        if (this.nameY === null || this.nameY === undefined || this.nameY === '') {
                            this.nameY = Math.min(this.srcH - 40, this.qrY + this.qrSize + 80);
                        }
                    },

                    startDrag(target, evt) {
                        evt.preventDefault();
                        this.dragging = target;
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        if (target === 'qr') {
                            this.pointerOffsetX = evt.clientX - rect.left - (this.qrX * this.scale);
                            this.pointerOffsetY = evt.clientY - rect.top - (this.qrY * this.scale);
                        } else {
                            this.pointerOffsetX = 0;
                            this.pointerOffsetY = 0;
                        }
                        window.addEventListener('pointermove', this.boundMove);
                        window.addEventListener('pointerup', this.boundUp);
                    },

                    onMove(evt) {
                        if (!this.dragging) return;
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        const dx = evt.clientX - rect.left;
                        const dy = evt.clientY - rect.top;
                        if (this.dragging === 'qr') {
                            let sx = Math.round((dx - this.pointerOffsetX) / this.scale);
                            let sy = Math.round((dy - this.pointerOffsetY) / this.scale);
                            sx = Math.max(0, Math.min(this.srcW - this.qrSize, sx));
                            sy = Math.max(0, Math.min(this.srcH - this.qrSize, sy));
                            this.qrX = sx;
                            this.qrY = sy;
                        } else if (this.dragging === 'name') {
                            let sx = Math.round(dx / this.scale);
                            let sy = Math.round(dy / this.scale);
                            sx = Math.max(0, Math.min(this.srcW, sx));
                            sy = Math.max(0, Math.min(this.srcH, sy));
                            this.nameX = sx;
                            this.nameY = sy;
                        }
                    },

                    onUp() {
                        this.dragging = null;
                        window.removeEventListener('pointermove', this.boundMove);
                        window.removeEventListener('pointerup', this.boundUp);
                    },
                };
            };

        window.invitationLayoutEditor = function (initial) {
                return {
                    stallX: initial.stallX,
                    stallY: initial.stallY,
                    companyX: initial.companyX,
                    companyY: initial.companyY,
                    logoX: initial.logoX,
                    logoY: initial.logoY,
                    logoSize: initial.logoSize,
                    textColor: initial.textColor,
                    srcW: 0,
                    srcH: 0,
                    displayW: 0,
                    displayH: 0,
                    dragging: null,
                    pointerOffsetX: 0,
                    pointerOffsetY: 0,
                    boundMove: null,
                    boundUp: null,

                    init() {
                        this.boundMove = this.onMove.bind(this);
                        this.boundUp = this.onUp.bind(this);
                        window.addEventListener('resize', () => this.measure());
                        this.$nextTick(() => this.measure());
                    },

                    get scale() {
                        return this.srcW > 0 ? this.displayW / this.srcW : 1;
                    },

                    measure() {
                        const img = this.$refs.bg;
                        if (!img) return;
                        if (!img.complete || img.naturalWidth === 0) {
                            img.addEventListener('load', () => this.measure(), { once: true });
                            return;
                        }
                        this.srcW = img.naturalWidth;
                        this.srcH = img.naturalHeight;
                        this.displayW = img.clientWidth;
                        this.displayH = img.clientHeight;
                        this.seedDefaults();
                    },

                    seedDefaults() {
                        if (!this.srcW || !this.srcH) return;

                        if (!this.logoSize || this.logoSize < 1) {
                            const size = Math.round(Math.min(this.srcW, this.srcH) * 0.25);
                            this.logoSize = size;
                            this.logoX = Math.round((this.srcW - size) / 2);
                            this.logoY = Math.round(this.srcH * 0.15);
                        }
                        if (this.stallX === null || this.stallX === undefined || this.stallX === '') {
                            this.stallX = Math.round(this.srcW / 2);
                        }
                        if (this.stallY === null || this.stallY === undefined || this.stallY === '') {
                            this.stallY = Math.round(this.srcH * 0.65);
                        }
                        if (this.companyX === null || this.companyX === undefined || this.companyX === '') {
                            this.companyX = Math.round(this.srcW / 2);
                        }
                        if (this.companyY === null || this.companyY === undefined || this.companyY === '') {
                            this.companyY = Math.round(this.srcH * 0.75);
                        }
                    },

                    startDrag(target, evt) {
                        evt.preventDefault();
                        this.dragging = target;
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        if (target === 'logo') {
                            this.pointerOffsetX = evt.clientX - rect.left - (this.logoX * this.scale);
                            this.pointerOffsetY = evt.clientY - rect.top - (this.logoY * this.scale);
                        } else {
                            this.pointerOffsetX = 0;
                            this.pointerOffsetY = 0;
                        }
                        window.addEventListener('pointermove', this.boundMove);
                        window.addEventListener('pointerup', this.boundUp);
                    },

                    onMove(evt) {
                        if (!this.dragging) return;
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        const dx = evt.clientX - rect.left;
                        const dy = evt.clientY - rect.top;
                        if (this.dragging === 'logo') {
                            let sx = Math.round((dx - this.pointerOffsetX) / this.scale);
                            let sy = Math.round((dy - this.pointerOffsetY) / this.scale);
                            sx = Math.max(0, Math.min(this.srcW - this.logoSize, sx));
                            sy = Math.max(0, Math.min(this.srcH - this.logoSize, sy));
                            this.logoX = sx;
                            this.logoY = sy;
                        } else {
                            let sx = Math.round(dx / this.scale);
                            let sy = Math.round(dy / this.scale);
                            sx = Math.max(0, Math.min(this.srcW, sx));
                            sy = Math.max(0, Math.min(this.srcH, sy));
                            if (this.dragging === 'stall') {
                                this.stallX = sx;
                                this.stallY = sy;
                            } else if (this.dragging === 'company') {
                                this.companyX = sx;
                                this.companyY = sy;
                            }
                        }
                    },

                    onUp() {
                        this.dragging = null;
                        window.removeEventListener('pointermove', this.boundMove);
                        window.removeEventListener('pointerup', this.boundUp);
                    },
                };
            };
    </script>
@endonce
