<div class="container max-w-2xl px-4 py-12 mx-auto">
    <div class="mb-8 text-center">
        <flux:heading size="xl" class="mb-2">Create Support Ticket</flux:heading>
        <flux:text>Submit a support ticket for your rejected, refunded, or cancelled booking</flux:text>
    </div>

    @if (!$bookingVerified)
        {{-- Step 1: Verify Booking --}}
        <flux:card class="p-6">
            <form wire:submit="verifyBooking">
                <flux:field>
                    <flux:label>Booking Code</flux:label>
                    <flux:input wire:model="bookingCode" placeholder="Enter your booking code" />
                    <flux:error name="bookingCode" />
                </flux:field>

                <div class="mt-6">
                    <flux:button type="submit" variant="primary">Verify Booking</flux:button>
                </div>
            </form>
        </flux:card>
    @else
        {{-- Step 2: Upload Documents --}}
        <flux:card class="p-6">
            <div class="mb-6">
                <flux:heading size="lg" class="mb-2">Booking Details</flux:heading>
                <div class="space-y-2">
                    <flux:text><strong>Booking Code:</strong> {{ $booking->booking_code }}</flux:text>
                    <flux:text><strong>Brand Name:</strong> {{ $booking->brand_name }}</flux:text>
                    <flux:text><strong>Status:</strong>
                        <flux:badge :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge>
                    </flux:text>
                </div>
            </div>

            <flux:separator class="my-6" />

            <div class="mb-6">
                <flux:heading size="lg" class="mb-4">Upload Documents</flux:heading>
                <flux:text class="mb-4">Upload up to 6 documents (max 5MB each). Supported formats: PDF, JPG, PNG
                </flux:text>

                {{-- File Upload Area --}}
                <div x-data="fileUploader(@entangle('uploadedDocuments'))" class="space-y-4">
                    <div x-show="files.length < 6" style="display: block;"
                        class="p-8 text-center border-2 border-dashed rounded-lg border-zinc-300 dark:border-zinc-600"
                        @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-950': isDragging }">
                        <input type="file" x-ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.jpeg,.png"
                            multiple class="hidden">

                        <div class="space-y-2">
                            <flux:icon.cloud-arrow-up variant="outline" class="w-12 h-12 mx-auto text-zinc-400" />
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                <button type="button" @click="$refs.fileInput.click()"
                                    class="font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                    Click to upload
                                </button>
                                or drag and drop
                            </div>
                            <flux:text class="text-xs">PDF, JPG, PNG up to 5MB</flux:text>
                        </div>
                    </div>

                    {{-- Upload Progress --}}
                    <div x-show="uploading" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <div class="w-full h-2 rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-2 transition-all duration-300 bg-blue-600 rounded-full"
                                        :style="`width: ${progress}%`"></div>
                                </div>
                            </div>
                            <flux:text class="text-sm" x-text="`${progress}%`"></flux:text>
                        </div>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400" x-text="currentFileName">
                        </flux:text>
                    </div>

                    {{-- Uploaded Files List --}}
                    <div x-show="files.length > 0" class="space-y-2">
                        <flux:heading size="base">Uploaded Documents (<span x-text="files.length"></span>/6)
                        </flux:heading>
                        <div class="space-y-2">
                            <template x-for="(file, index) in files" :key="index">
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon.document variant="outline" class="w-5 h-5 text-zinc-500" />
                                    <div class="flex-1">
                                        <flux:text class="text-sm font-medium" x-text="getFileName(file)"></flux:text>
                                    </div>
                                    <button type="button" @click="removeFile(index)"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        <flux:icon.x-mark variant="micro" class="w-5 h-5" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    @error('documents')
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button type="button" wire:click="submit" variant="primary"
                    :disabled="count($uploadedDocuments) === 0">
                    Submit Ticket
                </flux:button>
                <flux:button type="button" wire:click="$set('bookingVerified', false)" variant="ghost">
                    Change Booking
                </flux:button>
            </div>
        </flux:card>
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('fileUploader', (uploadedDocuments) => ({
                files: uploadedDocuments || [],
                uploading: false,
                progress: 0,
                currentFileName: '',
                isDragging: false,

                init() {
                    this.$watch('files', value => {
                        uploadedDocuments = value;
                    });
                },

                handleFileSelect(event) {
                    const files = Array.from(event.target.files);
                    if (files.length > 0) {
                        this.uploadMultipleFiles(files);
                    }
                },

                handleDrop(event) {
                    this.isDragging = false;
                    const files = Array.from(event.dataTransfer.files);
                    if (files.length > 0) {
                        this.uploadMultipleFiles(files);
                    }
                },

                async uploadMultipleFiles(files) {
                    for (const file of files) {
                        if (this.files.length >= 6) {
                            alert(
                                'Maximum 6 documents allowed. Remaining files were not uploaded.'
                            );
                            break;
                        }
                        await this.uploadFile(file);
                    }
                },

                async uploadFile(file) {
                    if (this.files.length >= 6) {
                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                        return;
                    }

                    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg',
                        'image/png'
                    ];
                    if (!allowedTypes.includes(file.type)) {
                        alert(
                            `File "${file.name}" has an invalid type. Only PDF, JPG, and PNG files are allowed.`
                        );
                        return;
                    }

                    this.uploading = true;
                    this.progress = 0;
                    this.currentFileName = file.name;

                    try {
                        const formData = new FormData();
                        formData.append('document', file);

                        const response = await fetch('/support-tickets/upload', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Upload failed');
                        }

                        const data = await response.json();

                        if (data.path) {
                            this.progress = 100;
                            await this.$wire.call('handleFileUpload', data);
                        }

                        setTimeout(() => {
                            this.uploading = false;
                            this.progress = 0;
                            this.currentFileName = '';
                        }, 500);

                    } catch (error) {
                        console.error('Upload error:', error);
                        alert(`Failed to upload "${file.name}": ${error.message}`);
                        this.uploading = false;
                        this.progress = 0;
                        this.currentFileName = '';
                    }

                    // Reset input
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                },

                removeFile(index) {
                    this.$wire.call('removeDocument', index);
                },

                getFileName(path) {
                    return path.split('/').pop();
                }
            }));
        });
    </script>
@endpush
</div>
