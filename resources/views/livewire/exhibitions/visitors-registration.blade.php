<div class="">
    <flux:main
        class="w-full max-w-2xl p-4 mx-auto space-y-6 sm:p-6 lg:p-8 lg:flex lg:flex-col lg:h-full lg:overflow-hidden">

        {{-- Header --}}
        <section class="space-y-3 lg:flex-shrink-0">
            <div class="flex items-center justify-between gap-2">
                <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="h-auto w-12 shrink-0 sm:w-16">
                <flux:heading size="xl" class="font-bold tracking-tight text-center text-base sm:text-xl">Visitor Registration</flux:heading>
                @if ($exhibition?->logo_url)
                    <img src="{{ $exhibition->logo_url }}" alt="{{ $exhibition->title }} Logo" class="h-auto w-24 shrink-0 sm:w-32">
                @endif
            </div>

            <div class="space-y-1 text-center">
                <flux:subheading>{{ $exhibition->title }}</flux:subheading>
                @if ($exhibition->start_date && $exhibition->end_date)
                    <flux:text size="sm" class="leading-snug">
                        <span class="inline-block">{{ $exhibition->start_date->format('M d') }}: 1:00 PM onwards</span>
                        <span class="inline-block text-zinc-400">&nbsp;|&nbsp;</span>
                        <span class="inline-block">{{ $exhibition->start_date->addDay()->format('M d') }} – {{ $exhibition->end_date->format('M d') }}: 10:00 AM onwards</span>
                    </flux:text>
                @endif
            </div>
        </section>

        {{-- Registration Closed --}}
        @if ($exhibition->registration_closed)
            <div class="flex flex-col items-center justify-center py-12 space-y-4 text-center">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="heart" class="size-8 text-amber-500" />
                </div>
                <div class="space-y-2">
                    <flux:heading size="lg" class="font-bold">Thank You for the Overwhelming Response!</flux:heading>
                    <flux:text class="max-w-sm text-zinc-500 dark:text-zinc-400">
                        Visitor registration for this exhibition is now closed. We're truly grateful for the incredible support and enthusiasm from all of you.
                    </flux:text>
                    <flux:text class="max-w-sm text-zinc-500 dark:text-zinc-400">
                        We look forward to seeing you at our next exhibitions. Stay tuned!
                    </flux:text>
                </div>
            </div>
        @else

        {{-- Step Indicator --}}

        <div class="flex items-center gap-2">
            {{-- Step 1 --}}
            <div class="flex items-center gap-2">
                <div @class([
                    'flex items-center justify-center size-8 rounded-full text-sm font-semibold shrink-0 transition-colors',
                    'bg-blue-600 text-white' => $currentStep === 1,
                    'bg-green-500 text-white' => $currentStep > 1,
                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' => $currentStep < 1,
                ])>
                    @if ($currentStep > 1)
                        <flux:icon name="check" class="size-4" />
                    @else
                        1
                    @endif
                </div>
                <div>
                    <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">Your Details</flux:text>
                    <flux:text size="xs" class="hidden text-zinc-400 sm:block">Personal & business info</flux:text>
                </div>
            </div>

            <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>

            {{-- Step 2 --}}
            <div class="flex items-center gap-2">
                <div @class([
                    'flex items-center justify-center size-8 rounded-full text-sm font-semibold shrink-0 transition-colors',
                    'bg-blue-600 text-white' => $currentStep === 2,
                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' => $currentStep < 2,
                ])>
                    2
                </div>
                <div>
                    <flux:text size="sm" @class([
                        'font-medium',
                        'text-zinc-900 dark:text-white' => $currentStep === 2,
                        'text-zinc-400' => $currentStep !== 2,
                    ])>Additional Persons</flux:text>
                    <flux:text size="xs" class="hidden text-zinc-400 sm:block">Optional</flux:text>
                </div>
            </div>
        </div>

        {{-- Form Body --}}


        {{-- ── STEP 1 ── --}}
        <div @class([
            'block' => $currentStep === 1,
            'hidden' => $currentStep !== 1,
        ])>
            <form wire:submit="nextStep">
                <div class="py-4 space-y-5 sm:px-6 sm:py-6">

                    {{-- Phone + Name --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Phone No <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="phoneNumber" type="tel" placeholder="e.g. 9876543210" />
                            <flux:error name="phoneNumber" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Full Name <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="name" placeholder="Your full name" />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    {{-- State & City --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>State <span class="text-red-500">*</span></flux:label>
                            <flux:select variant="listbox" searchable wire:model.live="state" placeholder="Select State">
                                @foreach ($states as $stateOption)
                                    <flux:select.option value="{{ $stateOption }}">{{ $stateOption }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="state" />
                        </flux:field>

                        <flux:field>
                            <flux:label>City <span class="text-red-500">*</span></flux:label>
                            <flux:select variant="listbox" searchable wire:model="city" placeholder="Select City">
                                @foreach ($this->cities as $cityOption)
                                    <flux:select.option value="{{ $cityOption }}">{{ $cityOption }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="city" />
                        </flux:field>
                    </div>

                    {{-- Company + Designation --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Company Name</flux:label>
                            <flux:input wire:model="companyName" placeholder="Optional" />
                            <flux:error name="companyName" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Designation</flux:label>
                            <flux:input wire:model="designation" placeholder="Optional" />
                            <flux:error name="designation" />
                        </flux:field>
                    </div>

                    {{-- Segment + Email --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Segment</flux:label>
                            <flux:select variant="listbox" wire:model="segment" placeholder="Select Segment">
                                <flux:select.option value="Business">Business</flux:select.option>
                                <flux:select.option value="Job (Working Professional)">Job (Working Professional)</flux:select.option>
                                <flux:select.option value="Student">Student</flux:select.option>
                                <flux:select.option value="Housewife">Housewife</flux:select.option>
                                <flux:select.option value="Other">Other</flux:select.option>
                            </flux:select>
                            <flux:error name="segment" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Email</flux:label>
                            <flux:input wire:model="email" type="email" placeholder="your@email.com (optional)" />
                            <flux:error name="email" />
                        </flux:field>
                    </div>
                </div>

                {{-- Step 1 Footer --}}
                <div class="pb-4 sm:px-6 sm:pb-6">
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="w-full"
                        wire:loading.attr="disabled"
                        wire:target="nextStep"
                        icon-trailing="arrow-right"
                        iconVariant="outline"
                    >
                        <span wire:loading.remove wire:target="nextStep">Continue</span>
                        <span wire:loading wire:target="nextStep">Validating...</span>
                    </flux:button>
                </div>
            </form>
        </div>

        {{-- ── STEP 2 ── --}}
        <div @class([
            'block' => $currentStep === 2,
            'hidden' => $currentStep !== 2,
        ])>
            <form wire:submit="register">
                <div class="py-4 space-y-3 sm:p-6">

                    {{-- Primary Visitor (read-only summary) --}}
                    <div
                        class="flex items-center gap-3 px-4 py-3 border rounded-xl border-zinc-200 dark:border-zinc-700">
                        <div
                            class="flex items-center justify-center text-sm font-semibold text-blue-700 bg-blue-100 rounded-full size-9 dark:bg-blue-900/50 dark:text-blue-300 shrink-0">
                            {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">
                                {{ $name }}</flux:text>
                            <flux:text size="xs" class="text-zinc-400">{{ $phoneNumber }}</flux:text>
                        </div>
                        @if ($exhibition->isPaidEntry())
                            <flux:badge color="zinc" size="sm" class="shrink-0">
                                {{ Number::currency((float) $exhibition->entry_amount, 'INR') }}</flux:badge>
                        @endif
                    </div>

                    {{-- Additional Persons List --}}
                    @foreach ($additionalPersons as $index => $person)
                        <div class="flex items-start gap-3 px-4 py-3 border rounded-xl border-zinc-200 dark:border-zinc-700"
                            wire:key="person-{{ $index }}">
                            <div
                                class="flex items-center justify-center mt-1 text-sm font-semibold rounded-full size-9 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 shrink-0">
                                {{ $index + 2 }}
                            </div>
                            <div class="flex-1 min-w-0 space-y-2">
                                <div>
                                    <flux:input wire:model="additionalPersons.{{ $index }}.name"
                                        placeholder="Full name" size="sm" />
                                    <flux:error name="additionalPersons.{{ $index }}.name" />
                                </div>
                                <div>
                                    <flux:input wire:model="additionalPersons.{{ $index }}.phone_number"
                                        type="tel" placeholder="Phone number" size="sm" />
                                    <flux:error name="additionalPersons.{{ $index }}.phone_number" />
                                </div>
                            </div>
                            @if ($exhibition->isPaidEntry())
                                <flux:badge color="zinc" size="sm" class="mt-1 shrink-0">
                                    {{ Number::currency((float) $exhibition->entry_amount, 'INR') }}</flux:badge>
                            @endif
                            <flux:button wire:click="removePerson({{ $index }})" variant="ghost"
                                size="sm"
                                class="mt-0.5 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 shrink-0"
                                icon="trash" />
                        </div>
                    @endforeach

                    {{-- Add Person Button --}}
                    <flux:button wire:click="addPerson" variant="ghost"
                        class="w-full transition-colors border-2 border-dashed border-zinc-300 dark:border-zinc-700 hover:border-blue-400 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"
                        icon="plus">
                        Add Another Person
                    </flux:button>
                </div>

                {{-- Pricing Summary + Actions --}}
                <div class="pb-4 space-y-3 sm:px-6 sm:pb-6">
                    @if ($exhibition->isPaidEntry())
                        <div
                            class="text-sm border divide-y rounded-xl border-zinc-200 dark:border-zinc-700 divide-zinc-100 dark:divide-zinc-800">
                            <div class="flex justify-between px-4 py-2.5">
                                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Primary visitor
                                </flux:text>
                                <flux:text size="sm">
                                    {{ Number::currency((float) $exhibition->entry_amount, 'INR') }}</flux:text>
                            </div>
                            @if (count($additionalPersons) > 0)
                                <div class="flex justify-between px-4 py-2.5">
                                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                        {{ count($additionalPersons) }} additional
                                        {{ Str::plural('person', count($additionalPersons)) }}
                                    </flux:text>
                                    <flux:text size="sm">
                                        {{ Number::currency((float) $exhibition->entry_amount * count($additionalPersons), 'INR') }}
                                    </flux:text>
                                </div>
                            @endif
                            <div class="flex justify-between px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800 rounded-b-xl">
                                <flux:text size="sm" class="font-semibold">Total</flux:text>
                                <flux:text size="sm" class="font-semibold">
                                    {{ Number::currency((float) $this->totalAmount, 'INR') }}</flux:text>
                            </div>
                        </div>
                    @endif
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="w-full"
                        wire:loading.attr="disabled"
                        wire:target="register"
                        icon="{{ $exhibition->isPaidEntry() ? 'credit-card' : 'check' }}"
                        iconVariant="outline"
                    >
                        <span wire:loading.remove wire:target="register">
                            @if ($exhibition->isPaidEntry())
                                Register & Pay {{ Number::currency((float) $this->totalAmount, 'INR') }}
                            @else
                                Complete Registration
                            @endif
                        </span>
                        <span wire:loading wire:target="register">Processing...</span>
                    </flux:button>

                    <flux:button type="button" wire:click="previousStep" variant="ghost" class="w-full"
                        icon="arrow-left">
                        Back to Details
                    </flux:button>
                </div>
            </form>
        </div>


        @endif

    </flux:main>
</div>
