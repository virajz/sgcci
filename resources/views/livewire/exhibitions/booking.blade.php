<div class="lg:h-screen lg:flex lg:flex-col">
    <flux:main class="w-full p-4 mx-auto space-y-6 sm:p-6 lg:p-8 lg:flex lg:flex-col lg:h-full lg:overflow-hidden">
        <section class="flex items-center justify-between mb-6 lg:flex-shrink-0">
            <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
            <flux:heading size="xl" class="font-bold tracking-tight">Stall Booking</flux:heading>
            <img src="{{ asset('brand/sgcci-logo.png') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
        </section>

        <div class="grid grid-cols-1 gap-y-6 lg:grid-cols-3 lg:gap-8 lg:flex-1 lg:overflow-hidden">
            {{-- Map Section (Left) --}}
            <section class="lg:col-span-2 lg:overflow-y-auto lg:pr-4">
                <div class="lg:sticky lg:top-0">
                    @livewire('stall-selector')
                </div>
            </section>

            <flux:separator class="my-6 lg:hidden" />

            {{-- Form Section (Right) --}}
            <section class="lg:order-2 lg:flex lg:flex-col lg:overflow-hidden">
                <form action="" class="flex flex-col lg:h-full lg:overflow-hidden">
                    {{-- Scrollable content area --}}
                    <div class="flex flex-col gap-6 lg:flex-1 lg:overflow-y-auto lg:pr-4">
                        @if (count($selectedStalls) > 0)
                            <flux:callout variant="info">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="lg">Selected Stalls ({{ count($selectedStalls) }})
                                    </flux:heading>
                                    <flux:button wire:click="clearSelectedStalls" variant="ghost" size="sm"
                                        icon="x-mark">
                                        Clear
                                    </flux:button>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach ($selectedStalls as $stall)
                                        <flux:badge size="lg" variant="solid" color="sky">{{ $stall }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </flux:callout>
                        @endif

                        <flux:fieldset>
                            <flux:legend>Company & Contact</flux:legend>

                            <div class="space-y-6">

                                <flux:input wire:model="brandName" label="Brand / Dealership Name"
                                    placeholder="Your company name" />

                                <div class="grid gap-6 lg:grid-cols-2">

                                    <flux:input wire:model="contactPerson" label="Contact Person"
                                        placeholder="Full name" />

                                    <flux:field>
                                        <flux:label>Phone Number</flux:label>
                                        <flux:input.group>
                                            <flux:select wire:model="phoneCode" variant="listbox" class="max-w-fit">
                                                <flux:select.option selected>+91</flux:select.option>
                                            </flux:select>
                                            <flux:input wire:model="phoneNumber" mask="99999 99999"
                                                placeholder="98765 43210" />
                                        </flux:input.group>
                                    </flux:field>

                                    <flux:input wire:model="email" type="email" label="Email"
                                        placeholder="you@example.com" />

                                    <flux:select wire:model="city" variant="listbox" searchable label="Select City">
                                        <flux:select.option>Ahmedabad</flux:select.option>
                                        <flux:select.option>Baroda</flux:select.option>
                                        <flux:select.option>Delhi</flux:select.option>
                                        <flux:select.option>Mumbai</flux:select.option>
                                        <flux:select.option selected>Surat</flux:select.option>
                                    </flux:select>
                                </div>
                            </div>
                        </flux:fieldset>

                        <flux:separator class="my-4" />

                        <flux:fieldset>
                            <flux:legend>Product Profile</flux:legend>

                            <flux:checkbox.group wire:model="productProfile" variant="cards"
                                class="grid gap-4 md:grid-cols-2">
                                <flux:checkbox checked value="4-wheelers" label="4-Wheelers" />
                                <flux:checkbox value="2-wheelers" label="2-Wheelers" />
                                <flux:checkbox value="commercial-3-wheelers" label="Commercial / 3-Wheelers" />
                                <flux:checkbox value="automobile-ancillaries" label="Automobile Ancillaries" />
                            </flux:checkbox.group>
                        </flux:fieldset>

                        <flux:separator class="my-4" />

                        <flux:fieldset>
                            <flux:legend>Past Participation</flux:legend>

                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="lg">Have you exhibited before?</flux:heading>
                                    <flux:switch wire:model.live="hasExhibitedBefore" />
                                </div>

                                @if ($hasExhibitedBefore)
                                    <div>
                                        <flux:subheading class="mb-4">Select year(s)</flux:subheading>
                                        <flux:checkbox.group wire:model="participationYears" variant="cards"
                                            class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                                            <flux:checkbox value="2011" label="2011" />
                                            <flux:checkbox value="2013" label="2013" />
                                            <flux:checkbox value="2015" label="2015" />
                                            <flux:checkbox value="2017" label="2017" />
                                            <flux:checkbox value="2019" label="2019" />
                                            <flux:checkbox value="2024" label="2024" />
                                        </flux:checkbox.group>
                                    </div>
                                @endif
                            </div>
                        </flux:fieldset>

                        <flux:separator class="my-4" />

                        <flux:fieldset>
                            <flux:legend>SGCCI Membership</flux:legend>

                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="lg">Are you an SGCCI member?</flux:heading>
                                    <flux:switch wire:model.live="isSgcciMember" />
                                </div>

                                @if ($isSgcciMember)
                                    <div>
                                        <flux:subheading class="mb-4">Select membership type</flux:subheading>
                                        <flux:radio.group wire:model="membershipType" variant="cards"
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
                                @endif
                            </div>
                        </flux:fieldset>

                        <flux:separator class="my-4" />
                    </div>

                    {{-- Sticky submit button --}}
                    <div class="flex justify-end pt-4 mt-6 lg:flex-shrink-0 lg:sticky lg:bottom-0">
                        <flux:button type="submit" variant="primary" class="w-full lg:w-fit">Submit Booking
                        </flux:button>
                    </div>
                </form>
            </section>
        </div>
    </flux:main>
</div>
