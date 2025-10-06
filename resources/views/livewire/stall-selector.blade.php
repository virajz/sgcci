<div x-data="{
    selectedStalls: @entangle('selectedStalls'),
    svgElement: null,
    viewBox: { x: 0, y: 0, width: 0, height: 0 },
    originalViewBox: { x: 0, y: 0, width: 0, height: 0 },
    isPanning: false,
    hasMoved: false,
    startPoint: { x: 0, y: 0 },
    scale: 1,
    lastTouchDistance: 0,
    lastTouchCenter: { x: 0, y: 0 },
    pendingUpdate: false,
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
        if (!this.svgElement || this.pendingUpdate) return;

        this.pendingUpdate = true;
        requestAnimationFrame(() => {
            if (this.svgElement) {
                this.svgElement.setAttribute('viewBox',
                    `${this.viewBox.x} ${this.viewBox.y} ${this.viewBox.width} ${this.viewBox.height}`
                );
            }
            this.pendingUpdate = false;
        });
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
        this.hasMoved = false;
        this.startPoint = {
            x: e.clientX,
            y: e.clientY
        };
    },
    pan(e) {
        if (!this.isPanning || !this.svgElement) return;

        const currentX = e.clientX;
        const currentY = e.clientY;
        const rect = this.svgElement.getBoundingClientRect();

        const dx = (this.startPoint.x - currentX) * (this.viewBox.width / rect.width);
        const dy = (this.startPoint.y - currentY) * (this.viewBox.height / rect.height);

        if (Math.abs(dx) > 0.1 || Math.abs(dy) > 0.1) {
            this.hasMoved = true;
            this.viewBox.x += dx;
            this.viewBox.y += dy;
            this.updateViewBox();
            this.startPoint = { x: currentX, y: currentY };
        }
    },
    endPan() {
        this.isPanning = false;
        setTimeout(() => { this.hasMoved = false; }, 100);
    },
    getPointInSVG(e) {
        if (!this.svgElement) return { x: 0, y: 0 };

        const rect = this.svgElement.getBoundingClientRect();
        const clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
        const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
        const x = this.viewBox.x + (clientX - rect.left) * (this.viewBox.width / rect.width);
        const y = this.viewBox.y + (clientY - rect.top) * (this.viewBox.height / rect.height);

        return { x, y };
    },
    getTouchDistance(touches) {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    },
    getTouchCenter(touches) {
        return {
            x: (touches[0].clientX + touches[1].clientX) / 2,
            y: (touches[0].clientY + touches[1].clientY) / 2
        };
    },
    handleTouchStart(e) {
        if (!this.svgElement) return;

        const target = e.target;
        const isStall = target.closest('[data-stall]');

        if (e.touches.length === 2) {
            e.preventDefault();
            this.lastTouchDistance = this.getTouchDistance(e.touches);
            this.lastTouchCenter = this.getTouchCenter(e.touches);
        } else if (e.touches.length === 1) {
            if (this.scale > 1 && !isStall) {
                this.isPanning = true;
                this.hasMoved = false;
                this.startPoint = {
                    x: e.touches[0].clientX,
                    y: e.touches[0].clientY
                };
            }
        }
    },
    handleTouchMove(e) {
        if (!this.svgElement) return;

        if (e.touches.length === 2) {
            e.preventDefault();
            const currentDistance = this.getTouchDistance(e.touches);

            // Pinch zoom
            const zoomFactor = this.lastTouchDistance / currentDistance;
            const newWidth = this.viewBox.width * zoomFactor;
            const newHeight = this.viewBox.height * zoomFactor;

            // Clamp zoom
            if (newWidth >= this.originalViewBox.width * 0.05 && newWidth <= this.originalViewBox.width) {
                const rect = this.svgElement.getBoundingClientRect();
                const centerX = this.viewBox.x + (this.lastTouchCenter.x - rect.left) * (this.viewBox.width / rect.width);
                const centerY = this.viewBox.y + (this.lastTouchCenter.y - rect.top) * (this.viewBox.height / rect.height);

                this.viewBox.width = newWidth;
                this.viewBox.height = newHeight;
                this.viewBox.x = centerX - (this.lastTouchCenter.x - rect.left) * (this.viewBox.width / rect.width);
                this.viewBox.y = centerY - (this.lastTouchCenter.y - rect.top) * (this.viewBox.height / rect.height);

                this.scale = this.originalViewBox.width / this.viewBox.width;
                this.updateViewBox();
            }

            this.lastTouchDistance = currentDistance;
        } else if (e.touches.length === 1 && this.isPanning && this.scale > 1) {
            e.preventDefault();

            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            const rect = this.svgElement.getBoundingClientRect();

            const dx = (this.startPoint.x - currentX) * (this.viewBox.width / rect.width);
            const dy = (this.startPoint.y - currentY) * (this.viewBox.height / rect.height);

            if (Math.abs(dx) > 0.1 || Math.abs(dy) > 0.1) {
                this.hasMoved = true;
                this.viewBox.x += dx;
                this.viewBox.y += dy;
                this.updateViewBox();
                this.startPoint = { x: currentX, y: currentY };
            }
        }
    },
    handleTouchEnd(e) {
        if (e.touches.length === 0) {
            this.isPanning = false;
            this.lastTouchDistance = 0;
            setTimeout(() => { this.hasMoved = false; }, 100);
        }
    }
}" class="relative w-full space-y-6">
    <div class="relative p-4 overflow-hidden border rounded-lg" style="touch-action: none;">
        <div class="select-none">
            <object data="{{ asset('maps/Main.svg') }}" type="image/svg+xml"
                class="w-full h-auto px-4 py-6 bg-white rounded"
                x-on:load="
                    const component = $data;
                    const svgDoc = $el.contentDocument;
                    svgElement = svgDoc.querySelector('svg');
                    initViewBox();

                    // Optimize SVG rendering
                    svgElement.style.willChange = 'transform';

                    // Add pan/zoom handlers to SVG background
                    svgElement.addEventListener('mousedown', (e) => {
                        const target = e.target;
                        if (!target.closest('[data-stall]')) {
                            component.startPan(e);
                        }
                    });
                    svgElement.addEventListener('mousemove', (e) => component.pan(e));
                    svgElement.addEventListener('mouseup', (e) => component.endPan());
                    svgElement.addEventListener('mouseleave', (e) => component.endPan());
                    svgElement.addEventListener('touchstart', (e) => component.handleTouchStart(e), { passive: false });
                    svgElement.addEventListener('touchmove', (e) => component.handleTouchMove(e), { passive: false });
                    svgElement.addEventListener('touchend', (e) => component.handleTouchEnd(e), { passive: false });

                    const stalls = svgDoc.querySelectorAll('[data-stall]');

                    stalls.forEach(stall => {
                        const stallNumber = stall.getAttribute('data-stall');

                        stall.style.cursor = 'pointer';

                        stall.addEventListener('click', () => {
                            if (!component.hasMoved) {
                                component.toggleStall(stallNumber);
                            }
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
    <div class="flex justify-center gap-2">
        <flux:button icon="plus" x-on:click="zoomIn" size="sm" class="shadow-lg" />
        <flux:button icon="minus" x-on:click="zoomOut" size="sm" class="shadow-lg" />
        <flux:button icon="arrow-path" x-on:click="resetZoom" size="sm" class="shadow-lg" />
    </div>
</div>
