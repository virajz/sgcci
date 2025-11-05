<div class="flex items-center justify-center min-h-screen p-4">
    <flux:main class="w-full max-w-4xl mx-auto space-y-8">
        <section class="flex items-center justify-between">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
            <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
        </section>

        <div class="text-center">
            <flux:heading size="xl" class="mb-4 text-3xl">Confirm Your Booking</flux:heading>
            <flux:subheading class="mb-8">
                Please review your booking details before confirming
            </flux:subheading>
        </div>

        <flux:card class="p-8">
            <div class="space-y-6">
                {{-- Contact Information --}}
                <div>
                    <flux:heading size="lg" class="mb-4">Contact Information</flux:heading>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:subheading class="text-sm">Brand / Dealership</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">
                                {{ $bookingData['brandName'] ?? '' }}</flux:text>
                        </div>
                        @if (!empty($bookingData['faciaName']))
                            <div>
                                <flux:subheading class="text-sm">Facia Name</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $bookingData['faciaName'] }}</flux:text>
                            </div>
                        @endif
                        @if (!empty($bookingData['trophyName']))
                            <div>
                                <flux:subheading class="text-sm">Trophy Name</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $bookingData['trophyName'] }}</flux:text>
                            </div>
                        @endif
                        <div>
                            <flux:subheading class="text-sm">Contact Person</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">
                                {{ $bookingData['contactPerson'] ?? '' }}</flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">Email</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">
                                {{ $bookingData['email'] ?? '' }}</flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">Phone</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">
                                {{ $bookingData['phoneCode'] ?? '' }} {{ $bookingData['phoneNumber'] ?? '' }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="text-sm">City</flux:subheading>
                            <flux:text class="font-semibold text-black dark:text-white">{{ $bookingData['city'] ?? '' }}
                            </flux:text>
                        </div>
                        @if (!empty($bookingData['gstNumber']))
                            <div>
                                <flux:subheading class="text-sm">GST Number</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $bookingData['gstNumber'] }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                <flux:separator />

                {{-- Product Profile --}}
                <div>
                    <flux:subheading class="mb-2 text-sm">Product Profile</flux:subheading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($bookingData['productProfile'] ?? [] as $profile)
                            <flux:badge variant="outline">{{ ucwords(str_replace('-', ' ', $profile)) }}</flux:badge>
                        @endforeach
                    </div>
                </div>

                <flux:separator />

                {{-- Stall Line Items --}}
                <div>
                    <flux:heading size="lg" class="mb-4">Selected Stalls & Pricing</flux:heading>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-zinc-700">
                                    <th class="px-4 py-3 font-semibold text-left">Stall Number</th>
                                    <th class="px-4 py-3 font-semibold text-left">Size</th>
                                    <th class="px-4 py-3 font-semibold text-right">Area (sq m)</th>
                                    <th class="px-4 py-3 font-semibold text-right">Rate/sq m</th>
                                    <th class="px-4 py-3 font-semibold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->lineItems as $item)
                                    <tr class="border-b dark:border-zinc-700">
                                        <td class="px-4 py-3 font-medium">{{ $item['stall_number'] }}</td>
                                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                            {{ $item['size_display'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item['area'] }}</td>
                                        <td class="px-4 py-3 text-right">₹750.00</td>
                                        <td class="px-4 py-3 font-medium text-right">
                                            ₹{{ number_format($item['price'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pricing Summary --}}
                    <div class="pt-4 mt-6 space-y-3 border-t dark:border-zinc-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Area:</span>
                            <span class="font-medium">{{ $this->pricing['total_area'] }} sq m</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Subtotal:</span>
                            <span class="font-medium">₹{{ number_format($this->pricing['total_price'], 2) }}</span>
                        </div>

                        @if ($this->pricing['discount_percentage'] > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Discount
                                    ({{ number_format($this->pricing['discount_percentage'], 2) }}%):</span>
                                <span
                                    class="font-medium text-green-600 dark:text-green-400">-₹{{ number_format($this->pricing['discount_amount'], 2) }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">GST (18%):</span>
                            <span class="font-medium">₹{{ number_format($this->pricing['gst_amount'], 2) }}</span>
                        </div>

                        <flux:separator />

                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold">Total Amount:</span>
                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                ₹{{ number_format($this->pricing['total_with_gst'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:callout variant="info" class="mb-6">
            <flux:text>
                By confirming, you agree to the terms and conditions in the
                <a href="{{ asset('Application-Form-Auto-Expo-26.pdf') }}" target="_blank"
                    class="underline transition-colors hover:text-primary-600 dark:hover:text-primary-400">
                    Application Form
                </a>
            </flux:text>
        </flux:callout>

        {{-- Action Buttons --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row">
            <flux:button wire:click="goBack" variant="ghost" class="w-full sm:w-fit" icon="arrow-left"
                iconVariant="outline">
                Go Back & Edit
            </flux:button>

            <flux:button wire:click="confirm" variant="primary" class="w-full sm:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="confirm">Confirm Booking</span>
                <span wire:loading wire:target="confirm">Confirming...</span>
            </flux:button>
        </div>
    </flux:main>
</div>
