<div class="lg:h-screen lg:flex lg:flex-col">
    <flux:main
        class="w-full max-w-3xl p-4 mx-auto space-y-6 sm:p-6 lg:p-8 lg:flex lg:flex-col lg:h-full lg:overflow-hidden">
        <section class="flex items-center justify-between mb-6 lg:flex-shrink-0">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
            <flux:heading size="xl" class="font-bold tracking-tight">Visitor Registration</flux:heading>
            <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto max-w-32">
        </section>

        <form wire:submit="register" class="flex flex-col space-y-6 lg:flex-1 lg:overflow-hidden">
            <div class="text-center lg:flex-shrink-0">
                <flux:subheading class="mb-2">{{ $exhibition->title }}</flux:subheading>

                @if ($exhibition->start_date && $exhibition->end_date)
                    <flux:text size="sm" class="text-zinc-400">
                        {{ $exhibition->start_date->format('M d, Y') }} — {{ $exhibition->end_date->format('M d, Y') }}
                    </flux:text>
                @endif

                @if ($exhibition->isPaidEntry())
                    <flux:badge color="amber" class="mt-2">
                        Entry Fee: {{ Number::currency((float) $exhibition->entry_amount, 'INR') }}
                    </flux:badge>
                @else
                    <flux:badge color="green" class="mt-2">Free Entry</flux:badge>
                @endif
            </div>

            <div class="flex-1 space-y-6 lg:overflow-y-auto">
                {{-- Phone Number --}}
                <flux:field>
                    <flux:label>Phone No <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="phoneNumber" type="tel" placeholder="e.g. 9876543210" />
                    <flux:error name="phoneNumber" />
                </flux:field>

                {{-- Name --}}
                <flux:field>
                    <flux:label>Name <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Your full name" />
                    <flux:error name="name" />
                </flux:field>

                {{-- Company Name --}}
                <flux:field>
                    <flux:label>Name of Company</flux:label>
                    <flux:input wire:model="companyName" placeholder="Company name (optional)" />
                    <flux:error name="companyName" />
                </flux:field>

                {{-- Designation --}}
                <flux:field>
                    <flux:label>Designation</flux:label>
                    <flux:input wire:model="designation" placeholder="Your designation (optional)" />
                    <flux:error name="designation" />
                </flux:field>

                {{-- State & City --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>State <span class="text-red-500">*</span></flux:label>
                        <flux:select variant="listbox" wire:model.live="state" placeholder="Select State">
                            @foreach ($states as $stateOption)
                                <flux:select.option value="{{ $stateOption }}">{{ $stateOption }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="state" />
                    </flux:field>

                    <flux:field>
                        <flux:label>City <span class="text-red-500">*</span></flux:label>
                        <flux:select variant="listbox" wire:model="city" placeholder="Select City">
                            @foreach ($this->cities as $cityOption)
                                <flux:select.option value="{{ $cityOption }}">{{ $cityOption }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="city" />
                    </flux:field>
                </div>

                {{-- Email --}}
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="your@email.com (optional)" />
                    <flux:error name="email" />
                </flux:field>

                {{-- Business Segments --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Business Segments <span class="text-red-500">*</span></flux:label>
                        <flux:select variant="listbox" wire:model.live="businessSegment"
                            placeholder="Select Business Segment">
                            @foreach ($businessSegments as $segment)
                                <flux:select.option value="{{ $segment }}">{{ $segment }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="businessSegment" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Sub Business Segments <span class="text-red-500">*</span></flux:label>
                        <flux:select variant="listbox" wire:model="subBusinessSegment"
                            placeholder="Select Sub Business Segment">
                            @foreach ($this->subSegments as $subSegment)
                                <flux:select.option value="{{ $subSegment }}">{{ $subSegment }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="subBusinessSegment" />
                    </flux:field>
                </div>

            </div>

            <div class="lg:flex-shrink-0">
                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled"
                    wire:target="register">
                    <span wire:loading.remove wire:target="register">
                        @if ($exhibition->isPaidEntry())
                            Register & Pay {{ Number::currency((float) $exhibition->entry_amount, 'INR') }}
                        @else
                            Submit
                        @endif
                    </span>
                    <span wire:loading wire:target="register">Processing...</span>
                </flux:button>
            </div>
        </form>
    </flux:main>
</div>
