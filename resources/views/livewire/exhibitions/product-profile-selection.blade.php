<div class="lg:h-screen lg:flex lg:flex-col">
    <flux:main class="w-full max-w-4xl p-4 mx-auto space-y-6 sm:p-6 lg:p-8 lg:flex lg:flex-col lg:h-full lg:overflow-hidden">
        <section class="flex items-center justify-between mb-6 lg:flex-shrink-0">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
            <flux:heading size="xl" class="font-bold tracking-tight">Select Product Profile</flux:heading>
            <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
        </section>

        <form wire:submit="continue" class="flex flex-col space-y-6 lg:flex-1 lg:overflow-hidden">
            <div class="text-center lg:flex-shrink-0">
                <flux:subheading class="mb-2">Choose the product categories you want to exhibit</flux:subheading>
                <flux:text size="sm" class="text-zinc-400">Select one or more categories that match your business</flux:text>
            </div>

            <div class="flex-1 lg:overflow-y-auto">
                <flux:field>
                    <flux:checkbox.group wire:model="selectedProfiles" variant="cards" class="grid gap-4">
                        @error('selectedProfiles')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror

                        <flux:checkbox value="4-wheelers" label="4-Wheelers" description="Cars and SUVs" />
                        <flux:checkbox value="2-wheelers" label="2-Wheelers" description="Motorcycles and Scooters" />
                        <flux:checkbox value="automobile-ancillaries" label="Automobile Ancillaries"
                            description="Parts, Accessories, and Services" />
                    </flux:checkbox.group>
                </flux:field>
            </div>

            <div class="flex gap-4 lg:flex-shrink-0">
                <flux:button href="{{ route('welcome') }}" variant="ghost" class="flex-1">
                    Back
                </flux:button>
                <flux:button type="submit" variant="primary" class="flex-1">
                    Continue to Stall Selection
                </flux:button>
            </div>
        </form>
    </flux:main>
</div>
