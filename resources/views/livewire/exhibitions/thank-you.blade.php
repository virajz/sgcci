<div class="flex items-center justify-center min-h-screen p-4">
    <flux:main class="w-full max-w-3xl mx-auto">
        <div class="pb-6 mb-6 space-y-8 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
                <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
            </div>

            <div class="text-center">
                <div
                    class="flex items-center justify-center w-20 h-20 mx-auto mb-6 ring-[12px] bg-green-600 rounded-full ring-green-200 dark:ring-green-800">
                    <flux:icon.check variant="outline" class="w-12 h-12 text-white" />
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
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $booking->contact_person }}
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
                                <flux:text class="font-semibold text-black dark:text-white">{{ $booking->city }}
                                </flux:text>
                            </div>
                            @if ($booking->gst_number)
                                <div>
                                    <flux:subheading class="text-sm">GST Number</flux:subheading>
                                    <flux:text class="font-semibold text-black dark:text-white">
                                        {{ $booking->gst_number }}
                                    </flux:text>
                                </div>
                            @endif
                            <div>
                                <flux:subheading class="text-sm">Exhibition</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $exhibition->title }}</flux:text>
                            </div>
                        </div>

                        <flux:separator />

                        <div>
                            <flux:subheading class="mb-2 text-sm">Selected Stalls</flux:subheading>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b dark:border-zinc-700">
                                            <th class="px-4 py-3 font-semibold text-left">Stall Number</th>
                                            <th class="px-4 py-3 font-semibold text-left">Size</th>
                                            <th class="px-4 py-3 font-semibold text-right">Area (sq m)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (\App\Models\Booking::getStallLineItems($booking->selected_stalls) as $item)
                                            <tr class="border-b dark:border-zinc-700">
                                                <td class="px-4 py-3 font-medium">{{ $item['stall_number'] }}</td>
                                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                                    {{ $item['size_display'] }}</td>
                                                <td class="px-4 py-3 text-right">{{ $item['area'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <flux:separator />

                        <div>
                            <flux:subheading class="mb-2 text-sm">Pricing Details</flux:subheading>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Area:</span>
                                    <span class="font-medium">{{ $booking->total_area }} sq m</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Price per sq m:</span>
                                    <span
                                        class="font-medium">₹{{ number_format((float) $booking->price_per_sqm, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal:</span>
                                    <span
                                        class="font-medium">₹{{ number_format((float) $booking->total_price, 2) }}</span>
                                </div>
                                @if ($booking->discount_percentage > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Discount
                                            ({{ number_format((float) $booking->discount_percentage, 2) }}%):</span>
                                        <span
                                            class="font-medium text-green-600 dark:text-green-400">-₹{{ number_format((float) $booking->discount_amount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">GST (18%):</span>
                                    <span
                                        class="font-medium">₹{{ number_format((float) $booking->gst_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t dark:border-zinc-700">
                                    <span class="text-lg font-semibold">Total Amount:</span>
                                    <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                        ₹{{ number_format((float) $booking->total_with_gst, 2) }}
                                    </span>
                                </div>
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
                    Our team will review your booking and contact you shortly at {{ $booking->email }} with further
                    details
                    and payment information.
                </flux:text>
            </flux:callout>

            <div class="flex justify-center gap-4">
                <flux:button href="{{ route('exhibitions.booking.show', $booking->exhibition_id) }}" variant="outline"
                    icon="arrow-left" iconVariant="outline">
                    Back to Booking
                </flux:button>
                <flux:button href="{{ route('home') }}" variant="primary" icon="home" iconVariant="outline">
                    Go to Homepage
                </flux:button>
            </div>
        </div>
    </flux:main>
</div>
