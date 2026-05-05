<div class="flex items-center justify-center min-h-screen p-4 bg-zinc-100 dark:bg-zinc-900">
    <flux:main class="w-full max-w-3xl mx-auto space-y-8">
        <section class="flex items-center justify-between">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
            @if ($exhibition?->logo_url)
                <img src="{{ $exhibition->logo_url }}" alt="{{ $exhibition->title }} Logo" class="w-full h-auto max-w-32">
            @endif
        </section>

        <div class="text-center">
            <div
                class="inline-flex items-center justify-center w-20 h-20 mb-4 bg-green-100 rounded-full dark:bg-green-950/30">
                <flux:icon.check variant="outline" class="w-12 h-12 text-green-600 dark:text-green-400" />
            </div>
            <flux:heading size="xl" class="mb-2 text-green-600 dark:text-green-400">Registration
                Successful!</flux:heading>
            <flux:subheading>Your visitor registration has been confirmed.</flux:subheading>
        </div>

        {{-- Primary Visitor Pass --}}
        <div class="space-y-4">
            <flux:heading size="lg" class="text-center">
                {{ count($this->additionalPersonPasses) > 0 ? 'Your Visitor Passes' : 'Your Visitor Pass' }}
            </flux:heading>

            {{-- Primary visitor poster --}}
            @include('livewire.exhibitions.partials.visitor-pass-poster', [
                'passImageDataUri' => $passImageDataUri,
                'visitorName' => $visitor->name,
                'downloadUrl' => route('visitor-pass.download', [
                    'exhibition' => $exhibition->slug,
                    'registrationCode' => $visitor->registration_code,
                ]),
            ])

            {{-- Additional person posters --}}
            @foreach ($this->additionalPersonPasses as $pass)
                @include('livewire.exhibitions.partials.visitor-pass-poster', [
                    'passImageDataUri' => $pass['passImageDataUri'],
                    'visitorName' => $pass['name'],
                    'downloadUrl' => $pass['downloadUrl'],
                ])
            @endforeach
        </div>

        <flux:card class="p-8">
            <div class="space-y-4">
                <flux:heading size="lg" class="mb-4">Registration Details</flux:heading>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Registration Code</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">{{ $visitor->registration_code }}
                    </flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Exhibition</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">{{ $exhibition->title }}</flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Name</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">{{ $visitor->name }}</flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Phone</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">{{ $visitor->phone_number }}
                    </flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Status</flux:text>
                    <flux:badge :color="$visitor->status->color()">{{ $visitor->status->label() }}</flux:badge>
                </div>

                @php $totalPersons = 1 + count($visitor->additional_persons ?? []); @endphp
                @if ($totalPersons > 1)
                    <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:text class="text-zinc-600 dark:text-zinc-400">People Registered</flux:text>
                        <flux:text class="font-semibold text-black dark:text-white">{{ $totalPersons }}</flux:text>
                    </div>
                @endif

                @if ($exhibition->start_date && $exhibition->end_date)
                    <div class="flex items-center justify-between py-3">
                        <flux:text class="text-zinc-600 dark:text-zinc-400">Exhibition Dates</flux:text>
                        <flux:text class="font-semibold text-black dark:text-white">
                            {{ $exhibition->start_date->format('M d, Y') }} —
                            {{ $exhibition->end_date->format('M d, Y') }}
                        </flux:text>
                    </div>
                @endif
            </div>

            <div class="p-4 mt-6 rounded-lg bg-blue-50 dark:bg-blue-950/30">
                <div class="flex items-start gap-3">
                    <flux:icon.information-circle variant="outline"
                        class="flex-shrink-0 w-5 h-5 mt-0.5 text-blue-600 dark:text-blue-400" />
                    <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                        Please save your registration code <strong>{{ $visitor->registration_code }}</strong> for
                        reference. You can also download your QR code for easy access at the venue.
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </flux:main>
</div>
