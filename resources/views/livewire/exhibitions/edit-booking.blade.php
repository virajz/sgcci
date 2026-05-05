<div>
    <flux:main class="w-full max-w-4xl p-4 mx-auto space-y-6 sm:p-6 lg:p-8">
        <section class="flex items-center justify-between mb-6">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
            <flux:heading size="xl" class="font-bold tracking-tight">Edit Booking</flux:heading>
            @if ($booking?->exhibition?->logo_url)
                <img src="{{ $booking->exhibition->logo_url }}" alt="{{ $booking->exhibition->title }} Logo" class="w-full h-auto max-w-32">
            @endif
        </section>

        @if (!$bookingFound)
            {{-- Booking Code Entry Form --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Find Your Booking</flux:heading>
                <flux:text class="mb-6">Enter your booking code to edit your booking details. You can find your
                    booking
                    code in the confirmation email sent to you.</flux:text>

                <form wire:submit="findBooking" class="space-y-6">
                    <flux:field>
                        <flux:label>Booking Code</flux:label>
                        <flux:input wire:model="bookingCode" type="text" placeholder="Enter 8-character code"
                            maxlength="8" class="uppercase" x-mask:dynamic="$input.toUpperCase()" />
                        <flux:error name="bookingCode" />
                    </flux:field>

                    <div class="flex justify-between">
                        <flux:button href="{{ route('home') }}" variant="ghost" icon="arrow-left">
                            Back to Home
                        </flux:button>
                        <flux:button type="submit" variant="primary" icon="magnifying-glass"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="findBooking">Find Booking</span>
                            <span wire:loading wire:target="findBooking">Searching...</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        @else
            {{-- Payment Received - Cannot Edit --}}
            @if ($paymentReceived)
                <flux:card>
                    <flux:callout variant="warning">
                        <flux:heading size="lg">Payment Received - Cannot Edit</flux:heading>
                        <flux:text class="mt-2">
                            This booking cannot be edited as payment has already been received.
                        </flux:text>
                    </flux:callout>

                    {{-- Booking Details --}}
                    <div class="mt-6 space-y-4">
                        <flux:heading size="base" class="font-semibold">Booking Details</flux:heading>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Booking Code</flux:text>
                                <flux:text class="font-semibold">{{ $booking->booking_code }}</flux:text>
                            </div>

                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Company Name</flux:text>
                                <flux:text class="font-semibold">{{ $booking->brand_name }}</flux:text>
                            </div>

                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Contact Person</flux:text>
                                <flux:text class="font-semibold">{{ $booking->contact_person }}</flux:text>
                            </div>

                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Space Type</flux:text>
                                <flux:text class="font-semibold">{{ ucfirst($booking->space_type) }}</flux:text>
                            </div>
                        </div>

                        {{-- Stalls Selected --}}
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Stalls Selected</flux:text>
                            <flux:text class="font-semibold">
                                {{ implode(', ', $booking->selected_stalls) }}
                            </flux:text>
                        </div>

                        {{-- Payment Information --}}
                        <div class="pt-4 mt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:heading size="base" class="mb-3 font-semibold">Payment Information</flux:heading>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Amount</flux:text>
                                    <flux:text class="font-semibold">₹{{ number_format($booking->total_with_gst, 2) }}
                                    </flux:text>
                                </div>

                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Amount Paid</flux:text>
                                    <flux:text class="font-semibold text-green-600 dark:text-green-400">
                                        ₹{{ number_format($booking->amount_paid, 2) }}</flux:text>
                                </div>

                                @if ($booking->remaining_amount > 0)
                                    <div>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Remaining Amount
                                        </flux:text>
                                        <flux:text class="font-semibold text-amber-600 dark:text-amber-400">
                                            ₹{{ number_format($booking->remaining_amount, 2) }}</flux:text>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <flux:text class="mt-6 text-sm">
                        If you need to make changes to this booking, please contact support for assistance.
                    </flux:text>

                    <div class="flex justify-center gap-3 mt-8">
                        <flux:button href="{{ route('home') }}" variant="primary" icon="home"
                            class="w-full sm:w-auto">
                            Back to Home
                        </flux:button>
                    </div>
                </flux:card>
            @elseif ($updateSuccessful)
                {{-- Update Successful Confirmation --}}
                <flux:card class="p-8">
                    <div class="mb-6 text-center">
                        <div class="flex justify-center mb-4">
                            <div
                                class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full dark:bg-green-900/30">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <flux:heading size="xl" class="mb-2">Booking Updated Successfully!</flux:heading>
                        <flux:text class="text-lg">Your booking <strong>{{ $booking->booking_code }}</strong> has been
                            updated.</flux:text>
                    </div>

                    <flux:callout variant="info" class="mb-6">
                        <flux:heading size="lg">What's Next?</flux:heading>
                        <flux:text class="mt-2">
                            Your updated booking will go through the approval process again. You will receive an email
                            notification once it's reviewed by our team.
                        </flux:text>
                    </flux:callout>

                    <div class="space-y-6">
                        {{-- Contact Information --}}
                        <div>
                            <flux:heading size="lg" class="mb-4">Updated Booking Details</flux:heading>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <flux:subheading class="text-sm">Brand / Dealership</flux:subheading>
                                    <flux:text class="font-semibold text-black dark:text-white">
                                        {{ $booking->brand_name }}</flux:text>
                                </div>
                                <div>
                                    <flux:subheading class="text-sm">Contact Person</flux:subheading>
                                    <flux:text class="font-semibold text-black dark:text-white">
                                        {{ $booking->contact_person }}</flux:text>
                                </div>
                                <div>
                                    <flux:subheading class="text-sm">Email</flux:subheading>
                                    <flux:text class="font-semibold text-black dark:text-white">{{ $booking->email }}
                                    </flux:text>
                                </div>
                                <div>
                                    <flux:subheading class="text-sm">Phone</flux:subheading>
                                    <flux:text class="font-semibold text-black dark:text-white">
                                        {{ $booking->phone_code }} {{ $booking->phone_number }}</flux:text>
                                </div>
                            </div>
                        </div>

                        <flux:separator />

                        {{-- Selected Stalls --}}
                        <div>
                            <flux:heading size="lg" class="mb-4">Selected Stalls
                                ({{ count($booking->selected_stalls) }})</flux:heading>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($booking->selected_stalls as $stall)
                                    <flux:badge size="lg" variant="solid" color="sky">{{ $stall }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        </div>

                        <flux:separator />

                        {{-- Updated Pricing Summary --}}
                        <div>
                            <flux:heading size="lg" class="mb-4">Updated Pricing</flux:heading>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Area:</span>
                                    <span class="font-medium">{{ number_format($booking->total_area, 0) }} sq m</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Price per sq m:</span>
                                    <span class="font-medium">₹{{ number_format($booking->price_per_sqm, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal:</span>
                                    <span class="font-medium">₹{{ number_format($booking->total_price, 2) }}</span>
                                </div>

                                @if ($booking->discount_percentage > 0)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Discount
                                            ({{ number_format($booking->discount_percentage, 2) }}%):</span>
                                        <span
                                            class="font-medium text-green-600 dark:text-green-400">-₹{{ number_format($booking->discount_amount, 2) }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">GST (18%):</span>
                                    <span class="font-medium">₹{{ number_format($booking->gst_amount, 2) }}</span>
                                </div>

                                <flux:separator />

                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-semibold">Total Amount:</span>
                                    <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                        ₹{{ number_format($booking->total_with_gst, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center gap-3 mt-8">
                        <flux:button href="{{ route('home') }}" variant="primary" icon="home"
                            class="w-full sm:w-auto">
                            Back to Home
                        </flux:button>
                    </div>
                </flux:card>
            @else
                {{-- Edit Booking Form --}}
                <flux:card>
                    <div class="mb-6">
                        <flux:heading size="lg">Edit Booking: {{ $booking->booking_code }}</flux:heading>
                        <flux:text class="mt-2">Update your booking information below. Note: You cannot change the
                            selected
                            stalls. To select different stalls, please <a
                                href="{{ route('exhibitions.booking.show', $booking->exhibition) }}"
                                class="underline text-primary-600 dark:text-primary-400">create a new booking</a>.
                        </flux:text>
                    </div>

                    {{-- Display Selected Stalls (Read-only) --}}
                    <flux:callout variant="info" class="mb-6">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">Selected Stalls ({{ count($booking->selected_stalls) }})
                            </flux:heading>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($booking->selected_stalls as $stall)
                                <flux:badge size="lg" variant="solid" color="sky">{{ $stall }}
                                </flux:badge>
                            @endforeach
                        </div>
                        <flux:text class="mt-2 text-sm">
                            To select different stalls, <a
                                href="{{ route('exhibitions.booking.show', $booking->exhibition) }}"
                                class="underline text-primary-600 dark:text-primary-400">create a new booking</a>.
                        </flux:text>
                    </flux:callout>

                    <form wire:submit="updateBooking" class="space-y-6" x-data="{
                        city: @entangle('city').live,
                        hasExhibitedBefore: @entangle('hasExhibitedBefore').live,
                        isSgcciMember: @entangle('isSgcciMember').live
                    }">
                        @if ($errors->any())
                            <flux:callout variant="danger">
                                <flux:heading size="lg">Please fix the following errors:</flux:heading>
                                <ul class="mt-2 space-y-1 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </flux:callout>
                        @endif

                        <flux:accordion transition>
                            {{-- Company & Contact --}}
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>Company & Contact Information</flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="space-y-6">
                                        <flux:input wire:model="brandName" label="Brand / Dealership Name"
                                            placeholder="Your company name"
                                            error="{{ $errors->first('brandName') }}" />

                                        <flux:field>
                                            <flux:label badge="Optional">GST Number</flux:label>
                                            <flux:input wire:model="gstNumber" type="text" mask="99aaaaa9999a9a9"
                                                x-mask:dynamic="$input.toUpperCase()" placeholder="22AAAAA0000A1Z5"
                                                class="uppercase" />
                                            <flux:error name="gstNumber" />
                                        </flux:field>

                                        <div class="grid gap-6 lg:grid-cols-2">
                                            <flux:input wire:model="contactPerson" label="Contact Person"
                                                placeholder="Full name"
                                                error="{{ $errors->first('contactPerson') }}" />

                                            <flux:field>
                                                <flux:label badge="Optional">Designation</flux:label>
                                                <flux:input wire:model="designation" type="text"
                                                    placeholder="e.g., Manager, Director" />
                                                <flux:error name="designation" />
                                            </flux:field>

                                            <flux:field>
                                                <flux:label>Phone Number</flux:label>
                                                <flux:input.group>
                                                    <flux:select wire:model="phoneCode" variant="listbox"
                                                        class="max-w-fit">
                                                        <flux:select.option selected>+91</flux:select.option>
                                                    </flux:select>
                                                    <flux:input wire:model="phoneNumber" mask="99999 99999"
                                                        placeholder="98765 43210" />
                                                </flux:input.group>
                                                @error('phoneNumber')
                                                    <flux:error>{{ $message }}</flux:error>
                                                @enderror
                                            </flux:field>

                                            <flux:input wire:model="email" type="email" label="Email"
                                                placeholder="you@example.com"
                                                error="{{ $errors->first('email') }}" />

                                            <flux:field>
                                                <flux:label badge="Optional">Website</flux:label>
                                                <flux:input wire:model="website" type="url"
                                                    placeholder="https://www.example.com" />
                                                <flux:error name="website" />
                                            </flux:field>

                                            <flux:select x-model="city" variant="listbox" searchable
                                                label="Select City">
                                                <flux:select.option>Ahmedabad</flux:select.option>
                                                <flux:select.option>Bangalore</flux:select.option>
                                                <flux:select.option>Baroda</flux:select.option>
                                                <flux:select.option>Chennai</flux:select.option>
                                                <flux:select.option>Delhi</flux:select.option>
                                                <flux:select.option>Hyderabad</flux:select.option>
                                                <flux:select.option>Kolkata</flux:select.option>
                                                <flux:select.option>Mumbai</flux:select.option>
                                                <flux:select.option>Pune</flux:select.option>
                                                <flux:select.option selected>Surat</flux:select.option>
                                                <flux:select.option>Others</flux:select.option>
                                            </flux:select>

                                            <div x-show="city === 'Others'" x-cloak>
                                                <flux:input wire:model="customCity" label="Enter Your City"
                                                    placeholder="Enter city name"
                                                    error="{{ $errors->first('customCity') }}" />
                                            </div>
                                        </div>

                                        {{-- Address Fields --}}
                                        <div class="mt-6 space-y-4">
                                            <flux:field>
                                                <flux:label>Address</flux:label>
                                                <flux:textarea wire:model.live="address" rows="3"
                                                    placeholder="Enter your company address" />
                                                <flux:error name="address" />
                                            </flux:field>

                                            <flux:field variant="inline">
                                                <flux:checkbox wire:model.live="sameAsBillingAddress">
                                                </flux:checkbox>
                                                <flux:label>Same as Billing Address</flux:label>
                                            </flux:field>

                                            <flux:field>
                                                <flux:label>Billing Address</flux:label>
                                                <flux:textarea wire:model="billingAddress" rows="3"
                                                    placeholder="Enter billing address"
                                                    :disabled="$sameAsBillingAddress"
                                                    class="{{ $sameAsBillingAddress ? 'opacity-60 cursor-not-allowed' : '' }}" />
                                                <flux:error name="billingAddress" />
                                            </flux:field>
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>

                            {{-- Facia & Trophy Names --}}
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>Facia & Trophy Names (Optional)</flux:accordion.heading>
                                <flux:accordion.content>
                                    <div x-data="{ useBrandName: false }">
                                        <flux:field variant="inline">
                                            <flux:label>Copy brand name to both fields</flux:label>
                                            <flux:switch x-model="useBrandName"
                                                x-on:change="if (useBrandName) {
                                                $wire.set('faciaName', $wire.get('brandName'));
                                                $wire.set('trophyName', $wire.get('brandName'));
                                            } else {
                                                $wire.set('faciaName', '');
                                                $wire.set('trophyName', '');
                                            }" />
                                        </flux:field>

                                        <div class="grid gap-6 mt-6 lg:grid-cols-2">
                                            <flux:field>
                                                <flux:label badge="Optional">Facia Name</flux:label>
                                                <flux:input wire:model="faciaName" type="text"
                                                    placeholder="Name for facia board"
                                                    x-bind:disabled="useBrandName" />
                                                <flux:error name="faciaName" />
                                            </flux:field>

                                            <flux:field>
                                                <flux:label badge="Optional">Trophy Name</flux:label>
                                                <flux:input wire:model="trophyName" type="text"
                                                    placeholder="Name for trophy" x-bind:disabled="useBrandName" />
                                                <flux:error name="trophyName" />
                                            </flux:field>
                                        </div>

                                        {{-- Company Logo Upload --}}
                                        <div class="mt-6" x-data="logoUploader(@entangle('companyLogo'))">
                                            <flux:label>Company Logo</flux:label>
                                            <flux:text class="mb-3 text-sm text-zinc-600 dark:text-zinc-400">Upload
                                                your company logo (AI, CDR, or PSD format, max 20MB)</flux:text>

                                            <div x-show="!logo.filename" style="display: block;"
                                                class="p-8 text-center border-2 border-dashed rounded-lg border-zinc-300 dark:border-zinc-600"
                                                @dragover.prevent="isDragging = true"
                                                @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop"
                                                :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-950': isDragging }">
                                                <input type="file" x-ref="fileInput" @change="handleFileSelect"
                                                    accept=".ai,.cdr,.psd" class="hidden">

                                                <div class="space-y-2">
                                                    <flux:icon.cloud-arrow-up variant="outline"
                                                        class="w-12 h-12 mx-auto text-zinc-400" />
                                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                        <button type="button" @click="$refs.fileInput.click()"
                                                            class="font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                                            Click to upload
                                                        </button>
                                                        or drag and drop
                                                    </div>
                                                    <flux:text class="text-xs">AI, CDR, PSD up to 20MB</flux:text>
                                                </div>
                                            </div>

                                            {{-- Upload Progress --}}
                                            <div x-show="uploading" class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1">
                                                        <div
                                                            class="w-full h-2 rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                            <div class="h-2 transition-all duration-300 bg-blue-600 rounded-full"
                                                                :style="`width: ${progress}%`"></div>
                                                        </div>
                                                    </div>
                                                    <flux:text class="text-sm" x-text="`${progress}%`"></flux:text>
                                                </div>
                                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400"
                                                    x-text="currentFileName">
                                                </flux:text>
                                            </div>

                                            {{-- Uploaded File Display --}}
                                            <div x-show="logo.filename"
                                                class="p-3 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                                <div class="flex items-center gap-3">
                                                    <flux:icon.document variant="outline"
                                                        class="w-12 h-12 text-zinc-500" />
                                                    <div class="flex-1 min-w-0">
                                                        <flux:text class="text-sm font-medium truncate"
                                                            x-text="logo.filename">
                                                        </flux:text>
                                                    </div>
                                                    <button type="button" @click="removeLogo()"
                                                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                        <flux:icon.x-mark variant="micro" class="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </div>

                                            @error('companyLogo')
                                                <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">
                                                    {{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>

                            {{-- Product Profile --}}
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>Product Profile</flux:accordion.heading>
                                <flux:accordion.content>
                                    <flux:checkbox.group wire:model="productProfile" variant="cards"
                                        class="grid gap-4 md:grid-cols-2">
                                        @error('productProfile')
                                            <flux:error class="col-span-2">{{ $message }}</flux:error>
                                        @enderror
                                        <flux:checkbox value="4-wheelers" label="4-Wheelers" />
                                        <flux:checkbox value="2-wheelers" label="2-Wheelers" />
                                        <flux:checkbox value="commercial-3-wheelers"
                                            label="Commercial / 3-Wheelers" />
                                        <flux:checkbox value="automobile-ancillaries"
                                            label="Automobile Ancillaries" />
                                    </flux:checkbox.group>
                                </flux:accordion.content>
                            </flux:accordion.item>

                            {{-- Space Type --}}
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>Space Type</flux:accordion.heading>
                                <flux:accordion.content>
                                    <flux:subheading class="mb-4">Select the type of space you want</flux:subheading>
                                    <flux:radio.group wire:model.live="spaceType" variant="cards" class="grid gap-4">
                                        <flux:radio value="standard" label="Standard Space - ₹5,000/sq m">
                                            <x-slot:description>
                                                Fully fabricated stall with walls, flooring, and basic amenities<br>
                                                <strong>Applicable only for stalls: 6×3 (18 sq m) & 3×3 (9 sq
                                                    m)</strong>
                                            </x-slot:description>
                                        </flux:radio>
                                        <flux:radio value="raw" label="Raw Space - ₹4,500/sq m"
                                            description="Open space without fabrication - customize as per your needs" />
                                    </flux:radio.group>
                                    @error('spaceType')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:accordion.content>
                            </flux:accordion.item>

                            {{-- Past Participation --}}
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>Past Participation</flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between">
                                            <flux:heading size="lg">Have you exhibited before?</flux:heading>
                                            <flux:switch x-model="hasExhibitedBefore" />
                                        </div>

                                        <div x-show="hasExhibitedBefore" x-cloak>
                                            <flux:subheading class="mb-4">Select year(s)</flux:subheading>
                                            <flux:checkbox.group wire:model.live="participationYears" variant="cards"
                                                class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                                                <flux:checkbox value="2011" label="2011" />
                                                <flux:checkbox value="2013" label="2013" />
                                                <flux:checkbox value="2015" label="2015" />
                                                <flux:checkbox value="2017" label="2017" />
                                                <flux:checkbox value="2019" label="2019" />
                                                <flux:checkbox value="2024" label="2024" />
                                            </flux:checkbox.group>
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>

                            {{-- SGCCI Membership --}}
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>SGCCI Membership</flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between">
                                            <flux:heading size="lg">Are you an SGCCI member?</flux:heading>
                                            <flux:switch x-model="isSgcciMember" />
                                        </div>

                                        <div x-show="isSgcciMember" x-cloak>
                                            <flux:subheading class="mb-4">Select membership type</flux:subheading>
                                            <flux:radio.group wire:model.live="membershipType" variant="cards"
                                                class="grid gap-4">
                                                <flux:radio value="premium-member" label="Premium Member" />
                                                <flux:radio value="platinum-member" label="Platinum Member" />
                                                <flux:radio value="gold-member" label="Gold Member" />
                                                <flux:radio value="chief-patron" label="Chief Patron" />
                                                <flux:radio value="patron-member" label="Patron Member" />
                                                {{-- <flux:radio value="life-member" label="Life Member" />
                                                <flux:radio value="company-member" label="Company Member" /> --}}
                                            </flux:radio.group>

                                            <div x-show="$wire.membershipType" x-cloak class="mt-6">
                                                <flux:field>
                                                    <flux:label>Membership Number</flux:label>
                                                    <flux:input wire:model="membershipNumber"
                                                        placeholder="Enter your membership number" />
                                                    <flux:error name="membershipNumber" />
                                                </flux:field>
                                            </div>
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>
                        </flux:accordion>

                        {{-- Pricing Summary --}}
                        <flux:card class="mt-6">
                            <flux:heading size="lg" class="mb-4">Updated Pricing</flux:heading>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Area:</span>
                                    <span class="font-medium">{{ number_format($booking->total_area, 0) }} sq
                                        m</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Price per sq m:</span>
                                    <span
                                        class="font-medium">₹{{ number_format($this->updatedPricing()['price_per_sqm'], 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal:</span>
                                    <span
                                        class="font-medium">₹{{ number_format($this->updatedPricing()['total_price'], 2) }}</span>
                                </div>

                                @if ($this->updatedPricing()['discount_percentage'] > 0)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Discount
                                            ({{ number_format($this->updatedPricing()['discount_percentage'], 2) }}%):</span>
                                        <span
                                            class="font-medium text-green-600 dark:text-green-400">-₹{{ number_format($this->updatedPricing()['discount_amount'], 2) }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">GST (18%):</span>
                                    <span
                                        class="font-medium">₹{{ number_format($this->updatedPricing()['gst_amount'], 2) }}</span>
                                </div>

                                <flux:separator />

                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-semibold">Total Amount:</span>
                                    <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                        ₹{{ number_format($this->updatedPricing()['total_with_gst'], 2) }}
                                    </span>
                                </div>
                            </div>
                        </flux:card>

                        <div class="flex justify-between gap-3 pt-4 mt-6">
                            <flux:button wire:click="$set('bookingFound', false)" variant="ghost" icon="arrow-left">
                                Find Another
                            </flux:button>
                            <flux:button type="submit" variant="primary" icon="check"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="updateBooking">Update Booking</span>
                                <span wire:loading wire:target="updateBooking">Updating...</span>
                            </flux:button>
                        </div>
                    </form>
                </flux:card>
            @endif
        @endif
    </flux:main>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('logoUploader', (companyLogo) => ({
                logo: companyLogo || {},
                uploading: false,
                progress: 0,
                currentFileName: '',
                isDragging: false,

                init() {
                    this.$watch('logo', value => {
                        companyLogo = value;
                    });
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.uploadFile(file);
                    }
                },

                handleDrop(event) {
                    this.isDragging = false;
                    const file = event.dataTransfer.files[0];
                    if (file) {
                        this.uploadFile(file);
                    }
                },

                async uploadFile(file) {
                    if (file.size > 20 * 1024 * 1024) {
                        alert(`File "${file.name}" is too large. Maximum size is 20MB.`);
                        return;
                    }

                    const allowedTypes = ['application/postscript', 'application/illustrator',
                        'image/vnd.adobe.photoshop', 'application/x-photoshop'
                    ];
                    const extension = file.name.split('.').pop().toLowerCase();
                    const allowedExtensions = ['ai', 'cdr', 'psd'];

                    if (!allowedExtensions.includes(extension)) {
                        alert(
                            `File "${file.name}" has an invalid type. Only AI, CDR, and PSD files are allowed.`
                        );
                        return;
                    }

                    this.uploading = true;
                    this.progress = 0;
                    this.currentFileName = file.name;

                    try {
                        const formData = new FormData();
                        formData.append('logo', file);

                        const response = await fetch('/booking/upload-logo', {
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
                            await this.$wire.call('handleLogoUpload', data);
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

                removeLogo() {
                    this.$wire.call('removeLogo');
                }
            }));
        });
    </script>
@endpush
