<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Edit Booking #{{ $booking->booking_code }}</flux:heading>
            <flux:subheading class="mt-2">
                Update booking information for {{ $booking->brand_name }}
            </flux:subheading>
        </div>

        <flux:badge :color="$booking->status->color()" variant="solid" size="lg">
            {{ $booking->status->label() }}
        </flux:badge>
    </div>

    <flux:card>
        {{-- Display Selected Stalls (Read-only) --}}
        <flux:callout variant="info" class="mb-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Selected Stalls ({{ count($booking->selected_stalls) }})</flux:heading>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach ($booking->selected_stalls as $stall)
                    <flux:badge size="lg" variant="solid" color="sky">{{ $stall }}</flux:badge>
                @endforeach
            </div>
            <flux:text class="mt-2 text-sm">
                Stall selection cannot be modified. Contact system administrator if stall changes are required.
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
                                placeholder="Your company name" error="{{ $errors->first('brandName') }}" />

                            <flux:field>
                                <flux:label badge="Optional">GST Number</flux:label>
                                <flux:input wire:model="gstNumber" type="text" mask="99aaaaa9999a9a9"
                                    x-mask:dynamic="$uppercase($input)" placeholder="22AAAAA0000A1Z5"
                                    class="uppercase" />
                                <flux:error name="gstNumber" />
                            </flux:field>

                            <div class="grid gap-6 lg:grid-cols-2">
                                <flux:input wire:model="contactPerson" label="Contact Person" placeholder="Full name"
                                    error="{{ $errors->first('contactPerson') }}" />

                                <flux:field>
                                    <flux:label>Phone Number</flux:label>
                                    <flux:input.group>
                                        <flux:select wire:model="phoneCode" variant="listbox" class="max-w-fit">
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
                                    placeholder="you@example.com" error="{{ $errors->first('email') }}" />

                                <flux:select x-model="city" variant="listbox" searchable label="Select City">
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
                                        placeholder="Enter city name" error="{{ $errors->first('customCity') }}" />
                                </div>
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
                                    <flux:input wire:model="faciaName" type="text" placeholder="Name for facia board"
                                        x-bind:disabled="useBrandName" />
                                    <flux:error name="faciaName" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Optional">Trophy Name</flux:label>
                                    <flux:input wire:model="trophyName" type="text" placeholder="Name for trophy"
                                        x-bind:disabled="useBrandName" />
                                    <flux:error name="trophyName" />
                                </flux:field>
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
                            <flux:checkbox value="commercial-3-wheelers" label="Commercial / 3-Wheelers" />
                            <flux:checkbox value="automobile-ancillaries" label="Automobile Ancillaries" />
                        </flux:checkbox.group>
                    </flux:accordion.content>
                </flux:accordion.item>

                {{-- Space Type --}}
                <flux:accordion.item expanded>
                    <flux:accordion.heading>Space Type</flux:accordion.heading>
                    <flux:accordion.content>
                        <flux:subheading class="mb-4">Select the type of space you want</flux:subheading>
                        <flux:radio.group wire:model.live="spaceType" variant="cards" class="grid gap-4">
                            <flux:radio value="standard" label="Standard Space - ₹5,000/sq m"
                                description="Fully fabricated stall with walls, flooring, and basic amenities" />
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
                                    <flux:radio value="life-member" label="Life Member" />
                                    <flux:radio value="company-member" label="Company Member" />
                                </flux:radio.group>
                            </div>
                        </div>
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>

            <div class="flex gap-3 pt-4 mt-6 border-t dark:border-zinc-700">
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateBooking">Update Booking</span>
                    <span wire:loading wire:target="updateBooking">Updating...</span>
                </flux:button>
                <flux:button href="{{ route('admin.inquiries.show', $booking) }}" variant="ghost" icon="arrow-left">
                    Cancel
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
