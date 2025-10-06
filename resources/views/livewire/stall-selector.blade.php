<div x-data="{
    selectedStalls: @entangle('selectedStalls'),
    toggleStall(stallNumber) {
        $wire.toggleStall(stallNumber);
    },
    isSelected(stallNumber) {
        return this.selectedStalls.includes(stallNumber);
    }
}" class="w-full">
    <object data="{{ asset('maps/Main.svg') }}" type="image/svg+xml" class="w-full h-auto"
        x-on:load="
            const svg = $el.contentDocument;
            const stalls = svg.querySelectorAll('[data-stall]');

            stalls.forEach(stall => {
                const stallNumber = stall.getAttribute('data-stall');

                stall.style.cursor = 'pointer';

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
