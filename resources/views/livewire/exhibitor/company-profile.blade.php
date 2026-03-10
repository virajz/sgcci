<div class="space-y-6">
    {{-- Page header --}}
    <flux:card>
        <div class="space-y-1">
            <flux:heading size="xl">Company Profile</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                This message will be shown to customers who scan your QR code. Choose the type of message you want to
                display.
            </flux:text>
        </div>
    </flux:card>

    {{-- Profile message form --}}
    <flux:card>
        <div class="space-y-6">
            {{-- Message type selector --}}
            <div class="w-fit">
                <flux:radio.group wire:model.live="messageType" label="Message Type" variant="segmented">
                    <flux:radio label="Plain Text" value="text" />
                    <flux:radio label="Image with Text" value="image" />
                    <flux:radio label="Video with Text" value="video" />
                    <flux:radio label="PDF with Text" value="pdf" />
                </flux:radio.group>
            </div>

            {{-- Text field (shown for all types) --}}
            <flux:field x-data="{ count: {{ strlen($messageText) }} }">
                @if ($messageType !== 'text')
                    <flux:label badge="Optional">Message Text</flux:label>
                @else
                    <flux:label>Message Text</flux:label>
                @endif
                <flux:textarea
                    wire:model="messageText"
                    rows="6"
                    placeholder="Enter your message here..."
                    maxlength="2500"
                    x-on:input="count = $el.value.length"
                />
                <flux:error name="messageText" />
                <flux:description x-text="`${count} / 2500 characters`"></flux:description>
            </flux:field>

            {{-- Image upload (image type) --}}
            @if ($messageType === 'image')
                <div class="space-y-3">
                    <flux:file-upload wire:model="messageMedia" label="Image"
                        description="Max 5MB. JPG, PNG, GIF, or WEBP." :error="$errors->first('messageMedia')">
                        <flux:file-upload.dropzone heading="Drop image here or click to browse"
                            text="JPG, PNG, GIF, or WEBP, up to 5MB" with-progress inline />
                    </flux:file-upload>

                    @if ($messageMedia)
                        <flux:file-item :heading="$messageMedia->getClientOriginalName()"
                            :image="$messageMedia->temporaryUrl()" :size="$messageMedia->getSize()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="$set('messageMedia', null)"
                                    aria-label="Remove image" />
                            </x-slot>
                        </flux:file-item>
                    @elseif ($booking->profile_message_media)
                        <div class="space-y-2">
                            <img src="{{ route('exhibitor.profile-media', $booking) }}"
                                alt="Current image"
                                class="rounded-lg max-h-48 object-contain border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                <flux:icon.photo class="w-8 h-8 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <flux:text class="font-medium truncate">{{ $booking->profile_message_media_original_name }}</flux:text>
                                    <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">Current image</flux:text>
                                </div>
                                <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                    class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia"
                                    wire:confirm="Remove the current image?" />
                            </div>
                        </div>
                    @endif

                    <flux:error name="messageMedia" />
                </div>
            @endif

            {{-- Video upload (video type) --}}
            @if ($messageType === 'video')
                <div class="space-y-3">
                    <flux:file-upload wire:model="messageMedia" label="Video"
                        description="Max 16MB. MP4, MOV, AVI, or WEBM." :error="$errors->first('messageMedia')">
                        <flux:file-upload.dropzone heading="Drop video here or click to browse"
                            text="MP4, MOV, AVI, or WEBM, up to 16MB" with-progress inline />
                    </flux:file-upload>

                    @if ($messageMedia)
                        <flux:file-item :heading="$messageMedia->getClientOriginalName()"
                            :size="$messageMedia->getSize()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="$set('messageMedia', null)"
                                    aria-label="Remove video" />
                            </x-slot>
                        </flux:file-item>
                    @elseif ($booking->profile_message_media)
                        <div class="space-y-2">
                            <video controls class="rounded-lg max-h-48 w-full border border-zinc-200 dark:border-zinc-700">
                                <source src="{{ route('exhibitor.profile-media', $booking) }}">
                            </video>
                            <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                <flux:icon.film class="w-8 h-8 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <flux:text class="font-medium truncate">{{ $booking->profile_message_media_original_name }}</flux:text>
                                    <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">Current video</flux:text>
                                </div>
                                <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                    class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia"
                                    wire:confirm="Remove the current video?" />
                            </div>
                        </div>
                    @endif

                    <flux:error name="messageMedia" />
                </div>
            @endif

            {{-- PDF upload (pdf type) --}}
            @if ($messageType === 'pdf')
                <div class="space-y-3">
                    <flux:file-upload wire:model="messageMedia" label="PDF"
                        description="Max 16MB. PDF only." :error="$errors->first('messageMedia')">
                        <flux:file-upload.dropzone heading="Drop PDF here or click to browse"
                            text="PDF, up to 16MB" with-progress inline />
                    </flux:file-upload>

                    @if ($messageMedia)
                        <flux:file-item :heading="$messageMedia->getClientOriginalName()"
                            :size="$messageMedia->getSize()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="$set('messageMedia', null)"
                                    aria-label="Remove PDF" />
                            </x-slot>
                        </flux:file-item>
                    @elseif ($booking->profile_message_media)
                        <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                            <flux:icon.document class="w-8 h-8 text-zinc-400 dark:text-zinc-500 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium truncate">{{ $booking->profile_message_media_original_name }}</flux:text>
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">Current PDF</flux:text>
                            </div>
                            <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia"
                                wire:confirm="Remove the current PDF?" />
                        </div>
                    @endif

                    <flux:error name="messageMedia" />
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save Profile</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
