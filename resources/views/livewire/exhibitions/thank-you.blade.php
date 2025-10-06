<div class="flex items-center justify-center min-h-screen p-4">
    <flux:main class="w-full max-w-3xl mx-auto space-y-8">
        <section class="flex items-center justify-between">
            <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
            <img src="{{ asset('brand/sgcci-logo.png') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
        </section>

        <div class="text-center">
            <div
                class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full dark:bg-green-900">
                <flux:icon.check class="w-12 h-12 text-green-600 dark:text-green-400" />
            </div>

            <flux:heading size="xl" class="mb-4 text-3xl">Booking Confirmed!</flux:heading>

            <flux:subheading class="mb-8">
                Thank you for your booking. Your stall reservation has been successfully submitted.
            </flux:subheading>
        </div>

        <flux:card class="p-8">
            <div class="space-y-6">
                <div class="text-center">
                    <flux:subheading class="mb-2">Your Booking Code</flux:subheading>
                    <div
                        class="inline-flex items-center px-8 py-4 text-4xl font-bold tracking-widest border-2 border-dashed rounded-lg bg-zinc-50 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-600">
                        {{ $booking->booking_code }}
                    </div>
                    <flux:text class="block mt-2 text-sm text-zinc-500">
                        Please save this code for your records
                    </flux:text>
                </div>

                <flux:separator />

                <div class="space-y-4">
                    <flux:heading size="lg">Booking Details</flux:heading>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:subheading class="text-sm">Brand / Dealership</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">{{ $booking->brand_name }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">Contact Person</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">{{ $booking->contact_person }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">Email</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">{{ $booking->email }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">Phone</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">{{ $booking->phone_code }}
                                {{ $booking->phone_number }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">City</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">{{ $booking->city }}</flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">Exhibition</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">
                                {{ $booking->exhibition->title }}</flux:text>
                        </div>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:subheading class="mb-2 text-sm">Selected Stalls</flux:subheading>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($booking->selected_stalls as $stall)
                                <flux:badge size="lg" variant="solid" color="sky">{{ $stall }}
                                </flux:badge>
                            @endforeach
                        </div>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:subheading class="mb-2 text-sm">Product Profile</flux:subheading>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($booking->product_profile as $profile)
                                <flux:badge variant="outline">{{ ucwords(str_replace('-', ' ', $profile)) }}
                                </flux:badge>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:callout variant="info">
            <flux:heading size="lg">What's Next?</flux:heading>
            <flux:text class="mt-2">
                Our team will review your booking and contact you shortly at {{ $booking->email }} with further details
                and payment information.
            </flux:text>
        </flux:callout>

        <div class="flex justify-center gap-4">
            <flux:button href="{{ route('exhibitions.booking.show', $booking->exhibition) }}" variant="outline"
                icon="arrow-left">
                Back to Booking
            </flux:button>
            <flux:button href="{{ route('home') }}" variant="primary" icon="home">
                Go to Homepage
            </flux:button>
        </div>
    </flux:main>
</div>
