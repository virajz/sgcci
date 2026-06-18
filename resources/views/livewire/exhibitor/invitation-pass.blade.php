<div class="space-y-6">
    {{-- Page header --}}
    <flux:card>
        <div class="space-y-1">
            <flux:heading size="xl">Invitation Pass</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Upload your logo and enter your stall number and name to generate your personalised invitation pass.
            </flux:text>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Booking Code: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $booking->booking_code }}</span>
            </flux:text>
        </div>
    </flux:card>

    @if (! $exhibition?->hasCustomInvitationPass())
        <flux:callout icon="information-circle" variant="warning">
            <flux:callout.heading>Not available yet</flux:callout.heading>
            <flux:callout.text>
                The invitation pass for this exhibition has not been set up yet. Please check back later.
            </flux:callout.text>
        </flux:callout>
    @else
        <div
            x-data="{ v: 0 }"
            x-on:invitation-pass-updated.window="v++"
            class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start"
        >
            {{-- Form --}}
            <flux:card class="space-y-6">
                <flux:heading size="lg">Your Details</flux:heading>

                <flux:field>
                    <flux:label>Logo</flux:label>
                    <flux:description>Shown on your invitation pass. PNG with a transparent background works best.</flux:description>

                    <flux:file-upload wire:model="logoUpload" accept="image/png,image/jpeg,image/webp">
                        <flux:file-upload.dropzone
                            heading="Drop logo here or click to browse"
                            text="PNG, JPG or WebP, up to 5MB"
                        />
                    </flux:file-upload>
                    <flux:error name="logoUpload" />

                    @php
                        $logoPreviewUrl = $logoUpload ? $logoUpload->temporaryUrl() : $booking->invitation_logo_url;
                    @endphp

                    @if ($logoPreviewUrl)
                        <div class="flex items-center gap-3 p-3 mt-3 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                            <img src="{{ $logoPreviewUrl }}" alt="Logo preview" class="object-contain w-16 h-16 p-1 bg-white rounded" />
                            <div class="flex-1 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $logoUpload ? 'New logo selected — will save when you submit.' : 'Current logo.' }}
                            </div>
                            @if ($booking->invitation_logo_path && ! $logoUpload)
                                <flux:button size="sm" variant="ghost" type="button" wire:click="removeLogo" icon="trash" color="red">
                                    Remove
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label>Stall Number</flux:label>
                    <flux:input wire:model="stallNo" placeholder="e.g. A-42" />
                    <flux:error name="stallNo" />
                </flux:field>

                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="companyName" placeholder="Company / brand name" />
                    <flux:error name="companyName" />
                </flux:field>

                <flux:button variant="primary" wire:click="save" icon="check" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save Details</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </flux:card>

            {{-- Preview & download --}}
            <flux:card class="space-y-4">
                <flux:heading size="lg">Preview</flux:heading>

                <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
                    @if ($this->hasSavedDetails())
                        <img
                            :src="`{{ route('exhibitor.invitation-pass.preview', $booking) }}?v=${v}`"
                            alt="Invitation pass preview"
                            class="block w-full h-auto"
                        />
                    @else
                        <img
                            src="{{ $exhibition->invitation_background_url }}"
                            alt="Invitation pass base"
                            class="block w-full h-auto"
                        />
                    @endif
                </div>

                @if ($this->hasSavedDetails())
                    <flux:button
                        variant="primary"
                        icon="arrow-down-tray"
                        href="{{ route('exhibitor.invitation-pass.download', $booking) }}"
                        class="w-full"
                    >
                        Download Invitation Pass
                    </flux:button>
                @else
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        Fill in your details and save to generate your downloadable pass.
                    </flux:text>
                @endif
            </flux:card>
        </div>
    @endif
</div>
