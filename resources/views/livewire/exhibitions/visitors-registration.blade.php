<div class="flex items-center justify-center min-h-screen p-4">
    <flux:main class="w-full max-w-3xl mx-auto space-y-8">
        <section class="flex items-center justify-between">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
            <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
        </section>

        <div class="text-center">
            <flux:heading size="xl" class="mb-4 text-3xl">Visitor Registration</flux:heading>
            <flux:subheading class="mb-4">{{ $exhibition->title }}</flux:subheading>

            @if ($exhibition->start_date && $exhibition->end_date)
                <flux:text class="text-sm">
                    {{ $exhibition->start_date->format('M d, Y') }} — {{ $exhibition->end_date->format('M d, Y') }}
                </flux:text>
            @endif
        </div>

        <flux:card class="p-8">
            <div class="text-center space-y-4">
                <flux:icon icon="clock" size="xl" class="mx-auto text-zinc-400" />
                <flux:heading size="lg">Coming Soon</flux:heading>
                <flux:text>
                    Visitor registration for this exhibition will be available shortly.
                </flux:text>
            </div>
        </flux:card>
    </flux:main>
</div>
