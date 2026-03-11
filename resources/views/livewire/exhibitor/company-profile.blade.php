<div class="space-y-6">
    {{-- Page header --}}
    <flux:card>
        <div class="space-y-1">
            <flux:heading size="xl">Company Profile</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                These messages will be sent to customers who scan your QR code.
            </flux:text>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Booking Code: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $booking->booking_code }}</span>
            </flux:text>
        </div>
    </flux:card>

    {{-- Profile message form --}}
    <flux:card>
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Message 1 --}}
                <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="base">Message 1</flux:heading>

                    <div class="w-fit">
                        <flux:radio.group wire:model.live="messageType" label="Type" variant="segmented">
                            <flux:radio label="Text" value="text" />
                            <flux:radio label="Image" value="image" />
                            <flux:radio label="Video" value="video" />
                            <flux:radio label="PDF" value="pdf" />
                        </flux:radio.group>
                    </div>

                    <flux:field x-data="{ count: {{ strlen($messageText) }} }">
                        @if ($messageType !== 'text')
                            <flux:label badge="Optional">Message Text</flux:label>
                        @else
                            <flux:label>Message Text</flux:label>
                        @endif
                        <flux:textarea
                            wire:model="messageText"
                            rows="4"
                            placeholder="Enter your message here..."
                            maxlength="2500"
                            x-on:input="count = $el.value.length"
                        />
                        <flux:error name="messageText" />
                        <flux:description x-text="`${count} / 2500 characters`"></flux:description>
                    </flux:field>

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
                                        class="max-h-32 rounded-lg object-contain border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                        <flux:icon.photo class="w-6 h-6 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                        <flux:text class="flex-1 truncate font-medium text-sm">{{ $booking->profile_message_media_original_name }}</flux:text>
                                        <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                            class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia"
                                            wire:confirm="Remove the current image?" />
                                    </div>
                                </div>
                            @endif
                            <flux:error name="messageMedia" />
                        </div>
                    @endif

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
                                <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                    <flux:icon.film class="w-6 h-6 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                    <flux:text class="flex-1 truncate font-medium text-sm">{{ $booking->profile_message_media_original_name }}</flux:text>
                                    <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                        class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia"
                                        wire:confirm="Remove the current video?" />
                                </div>
                            @endif
                            <flux:error name="messageMedia" />
                        </div>
                    @endif

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
                                    <flux:icon.document class="w-6 h-6 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                    <flux:text class="flex-1 truncate font-medium text-sm">{{ $booking->profile_message_media_original_name }}</flux:text>
                                    <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                        class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia"
                                        wire:confirm="Remove the current PDF?" />
                                </div>
                            @endif
                            <flux:error name="messageMedia" />
                        </div>
                    @endif
                </div>

                {{-- Message 2 --}}
                <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="base">Message 2</flux:heading>

                    <div class="w-fit">
                        <flux:radio.group wire:model.live="message2Type" label="Type" variant="segmented">
                            <flux:radio label="Text" value="text" />
                            <flux:radio label="Image" value="image" />
                            <flux:radio label="Video" value="video" />
                            <flux:radio label="PDF" value="pdf" />
                        </flux:radio.group>
                    </div>

                    <flux:field x-data="{ count: {{ strlen($message2Text) }} }">
                        @if ($message2Type !== 'text')
                            <flux:label badge="Optional">Message Text</flux:label>
                        @else
                            <flux:label>Message Text</flux:label>
                        @endif
                        <flux:textarea
                            wire:model="message2Text"
                            rows="4"
                            placeholder="Enter your second message here..."
                            maxlength="2500"
                            x-on:input="count = $el.value.length"
                        />
                        <flux:error name="message2Text" />
                        <flux:description x-text="`${count} / 2500 characters`"></flux:description>
                    </flux:field>

                    @if ($message2Type === 'image')
                        <div class="space-y-3">
                            <flux:file-upload wire:model="message2Media" label="Image"
                                description="Max 5MB. JPG, PNG, GIF, or WEBP." :error="$errors->first('message2Media')">
                                <flux:file-upload.dropzone heading="Drop image here or click to browse"
                                    text="JPG, PNG, GIF, or WEBP, up to 5MB" with-progress inline />
                            </flux:file-upload>

                            @if ($message2Media)
                                <flux:file-item :heading="$message2Media->getClientOriginalName()"
                                    :image="$message2Media->temporaryUrl()" :size="$message2Media->getSize()">
                                    <x-slot name="actions">
                                        <flux:file-item.remove wire:click="$set('message2Media', null)"
                                            aria-label="Remove image" />
                                    </x-slot>
                                </flux:file-item>
                            @elseif ($booking->profile_message_2_media)
                                <div class="space-y-2">
                                    <img src="{{ route('exhibitor.profile-media-2', $booking) }}"
                                        alt="Current image"
                                        class="max-h-32 rounded-lg object-contain border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                        <flux:icon.photo class="w-6 h-6 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                        <flux:text class="flex-1 truncate font-medium text-sm">{{ $booking->profile_message_2_media_original_name }}</flux:text>
                                        <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                            class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia2"
                                            wire:confirm="Remove the current image?" />
                                    </div>
                                </div>
                            @endif
                            <flux:error name="message2Media" />
                        </div>
                    @endif

                    @if ($message2Type === 'video')
                        <div class="space-y-3">
                            <flux:file-upload wire:model="message2Media" label="Video"
                                description="Max 16MB. MP4, MOV, AVI, or WEBM." :error="$errors->first('message2Media')">
                                <flux:file-upload.dropzone heading="Drop video here or click to browse"
                                    text="MP4, MOV, AVI, or WEBM, up to 16MB" with-progress inline />
                            </flux:file-upload>

                            @if ($message2Media)
                                <flux:file-item :heading="$message2Media->getClientOriginalName()"
                                    :size="$message2Media->getSize()">
                                    <x-slot name="actions">
                                        <flux:file-item.remove wire:click="$set('message2Media', null)"
                                            aria-label="Remove video" />
                                    </x-slot>
                                </flux:file-item>
                            @elseif ($booking->profile_message_2_media)
                                <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                    <flux:icon.film class="w-6 h-6 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                    <flux:text class="flex-1 truncate font-medium text-sm">{{ $booking->profile_message_2_media_original_name }}</flux:text>
                                    <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                        class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia2"
                                        wire:confirm="Remove the current video?" />
                                </div>
                            @endif
                            <flux:error name="message2Media" />
                        </div>
                    @endif

                    @if ($message2Type === 'pdf')
                        <div class="space-y-3">
                            <flux:file-upload wire:model="message2Media" label="PDF"
                                description="Max 16MB. PDF only." :error="$errors->first('message2Media')">
                                <flux:file-upload.dropzone heading="Drop PDF here or click to browse"
                                    text="PDF, up to 16MB" with-progress inline />
                            </flux:file-upload>

                            @if ($message2Media)
                                <flux:file-item :heading="$message2Media->getClientOriginalName()"
                                    :size="$message2Media->getSize()">
                                    <x-slot name="actions">
                                        <flux:file-item.remove wire:click="$set('message2Media', null)"
                                            aria-label="Remove PDF" />
                                    </x-slot>
                                </flux:file-item>
                            @elseif ($booking->profile_message_2_media)
                                <div class="flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                                    <flux:icon.document class="w-6 h-6 text-zinc-400 dark:text-zinc-500 shrink-0" />
                                    <flux:text class="flex-1 truncate font-medium text-sm">{{ $booking->profile_message_2_media_original_name }}</flux:text>
                                    <flux:button variant="ghost" size="sm" icon="trash" iconVariant="outline"
                                        class="text-red-500 hover:text-red-600 shrink-0" wire:click="removeMedia2"
                                        wire:confirm="Remove the current PDF?" />
                                </div>
                            @endif
                            <flux:error name="message2Media" />
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save Profile</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
