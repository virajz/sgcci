<div x-data="{
    selectedStalls: @entangle('selectedStalls'),
    svgElement: null,
    viewBox: { x: 0, y: 0, width: 0, height: 0 },
    originalViewBox: { x: 0, y: 0, width: 0, height: 0 },
    isPanning: false,
    startPoint: { x: 0, y: 0 },
    scale: 1,
    toggleStall(stallNumber) {
        $wire.toggleStall(stallNumber);
    },
    isSelected(stallNumber) {
        return this.selectedStalls.includes(stallNumber);
    },
    initViewBox() {
        if (!this.svgElement) return;

        const vb = this.svgElement.viewBox.baseVal;
        this.viewBox = { x: vb.x, y: vb.y, width: vb.width, height: vb.height };
        this.originalViewBox = { ...this.viewBox };
    },
    updateViewBox() {
        if (!this.svgElement) return;

        this.svgElement.setAttribute('viewBox',
            `${this.viewBox.x} ${this.viewBox.y} ${this.viewBox.width} ${this.viewBox.height}`
        );
    },
    zoomIn() {
        if (!this.svgElement) return;

        const zoomFactor = 0.8;
        const newWidth = this.viewBox.width * zoomFactor;
        const newHeight = this.viewBox.height * zoomFactor;

        this.viewBox.x += (this.viewBox.width - newWidth) / 2;
        this.viewBox.y += (this.viewBox.height - newHeight) / 2;
        this.viewBox.width = newWidth;
        this.viewBox.height = newHeight;

        this.scale = this.originalViewBox.width / this.viewBox.width;
        this.updateViewBox();
    },
    zoomOut() {
        if (!this.svgElement) return;

        const zoomFactor = 1.25;
        const newWidth = Math.min(this.viewBox.width * zoomFactor, this.originalViewBox.width);
        const newHeight = Math.min(this.viewBox.height * zoomFactor, this.originalViewBox.height);

        this.viewBox.x -= (newWidth - this.viewBox.width) / 2;
        this.viewBox.y -= (newHeight - this.viewBox.height) / 2;
        this.viewBox.width = newWidth;
        this.viewBox.height = newHeight;

        // Clamp to original bounds
        this.viewBox.x = Math.max(this.originalViewBox.x, Math.min(this.viewBox.x, this.originalViewBox.x));
        this.viewBox.y = Math.max(this.originalViewBox.y, Math.min(this.viewBox.y, this.originalViewBox.y));

        this.scale = this.originalViewBox.width / this.viewBox.width;
        this.updateViewBox();
    },
    resetZoom() {
        if (!this.svgElement) return;

        this.viewBox = { ...this.originalViewBox };
        this.scale = 1;
        this.updateViewBox();
    },
    startPan(e) {
        if (!this.svgElement || this.scale <= 1) return;

        this.isPanning = true;
        this.startPoint = this.getPointInSVG(e);
    },
    pan(e) {
        if (!this.isPanning || !this.svgElement) return;

        const currentPoint = this.getPointInSVG(e);
        const dx = (this.startPoint.x - currentPoint.x);
        const dy = (this.startPoint.y - currentPoint.y);

        this.viewBox.x += dx;
        this.viewBox.y += dy;

        this.updateViewBox();
        this.startPoint = this.getPointInSVG(e);
    },
    endPan() {
        this.isPanning = false;
    },
    getPointInSVG(e) {
        if (!this.svgElement) return { x: 0, y: 0 };

        const rect = this.svgElement.getBoundingClientRect();
        const x = this.viewBox.x + (e.clientX - rect.left) * (this.viewBox.width / rect.width);
        const y = this.viewBox.y + (e.clientY - rect.top) * (this.viewBox.height / rect.height);

        return { x, y };
    }
}" class="relative w-full">
    <div class="relative overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-900">
        <div x-on:mousedown="startPan" x-on:mousemove="pan" x-on:mouseup="endPan" x-on:mouseleave="endPan"
            :class="{ 'cursor-grab': scale > 1 && !isPanning, 'cursor-grabbing': isPanning }" class="select-none">
            <object data="{{ asset('maps/Main.svg') }}" type="image/svg+xml" class="w-full h-auto pointer-events-none"
                x-on:load="
                    const svgDoc = $el.contentDocument;
                    svgElement = svgDoc.querySelector('svg');
                    initViewBox();

                    const stalls = svgDoc.querySelectorAll('[data-stall]');

                    stalls.forEach(stall => {
                        const stallNumber = stall.getAttribute('data-stall');

                        stall.style.cursor = 'pointer';
                        stall.style.pointerEvents = 'auto';

                        stall.addEventListener('click', () => {
                            toggleStall(stallNumber);
                        });

                        $watch('selectedStalls', value => {
                            const isSelected = value.includes(stallNumber);

                            // Get elements
                            const bgPath = stall.querySelector('path[fill]:not([class]):not([stroke])');
                            const textPaths = stall.querySelectorAll('path[class][fill]');

                            if (isSelected) {
                                // Change background color
                                if (bgPath && !bgPath.hasAttribute('data-original-fill')) {
                                    bgPath.setAttribute('data-original-fill', bgPath.getAttribute('fill'));
                                }
                                if (bgPath) {
                                    bgPath.setAttribute('fill', '#0ea5e9');
                                }

                                // Change text color
                                textPaths.forEach(textPath => {
                                    if (!textPath.hasAttribute('data-original-fill')) {
                                        textPath.setAttribute('data-original-fill', textPath.getAttribute('fill'));
                                    }
                                    if (textPath.getAttribute('data-original-fill') === '#000') {
                                        textPath.setAttribute('fill', '#fff');
                                    }
                                });
                            } else {
                                // Restore background color
                                if (bgPath && bgPath.hasAttribute('data-original-fill')) {
                                    const original = bgPath.getAttribute('data-original-fill');
                                    bgPath.setAttribute('fill', original);
                                    bgPath.removeAttribute('data-original-fill');
                                }

                                // Restore text color
                                textPaths.forEach(textPath => {
                                    if (textPath.hasAttribute('data-original-fill')) {
                                        const original = textPath.getAttribute('data-original-fill');
                                        textPath.setAttribute('fill', original);
                                        textPath.removeAttribute('data-original-fill');
                                    }
                                });
                            }
                        });
                    });
                ">
            </object>
        </div>
    </div>

    <!-- Zoom Controls -->
    <div class="absolute flex flex-col gap-2 bottom-4 right-4">
        <flux:button icon="arrow-path" x-on:click="resetZoom" variant="primary" size="sm" class="shadow-lg"
            x-show="scale > 1" />
        <flux:button icon="minus" x-on:click="zoomOut" variant="primary" size="sm" class="shadow-lg" />
        <flux:button icon="plus" x-on:click="zoomIn" variant="primary" size="sm" class="shadow-lg" />
    </div>
</div>
