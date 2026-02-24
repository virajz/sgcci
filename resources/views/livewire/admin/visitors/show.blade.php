<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Visitor Registration</flux:heading>
            <flux:text class="mt-1">
                Code: <span class="font-mono font-semibold">{{ $visitor->registration_code }}</span>
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button :href="route('admin.visitors.index')" variant="ghost" wire:navigate icon="arrow-left"
                iconVariant="micro">
                Back to List
            </flux:button>
            @if (in_array($visitor->status, [\App\VisitorRegistrationStatus::PaymentPending, \App\VisitorRegistrationStatus::PaymentFailed]))
                <flux:button variant="primary" icon="check-circle" wire:click="confirmMarkPaid">Mark as Paid</flux:button>
            @endif
            <flux:button variant="danger" icon="trash" wire:click="confirmDelete">Delete</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Details --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Registration Details --}}
            <flux:card>
                <div class="p-6">
                    <flux:heading size="lg" class="mb-4">Registration Details</flux:heading>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Full Name</flux:text>
                            <flux:text class="font-semibold">{{ $visitor->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Phone Number</flux:text>
                            <flux:text class="font-mono">{{ $visitor->phone_number }}</flux:text>
                        </div>
                        @if ($visitor->email)
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Email</flux:text>
                                <flux:text>{{ $visitor->email }}</flux:text>
                            </div>
                        @endif
                        @if ($visitor->company_name)
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Company</flux:text>
                                <flux:text>{{ $visitor->company_name }}</flux:text>
                            </div>
                        @endif
                        @if ($visitor->designation)
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Designation</flux:text>
                                <flux:text>{{ $visitor->designation }}</flux:text>
                            </div>
                        @endif
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Location</flux:text>
                            <flux:text>{{ $visitor->city }}@if ($visitor->state)
                                    , {{ $visitor->state }}
                                @endif
                            </flux:text>
                        </div>
                        @if ($visitor->business_segment)
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Business Segment</flux:text>
                                <flux:text>{{ $visitor->business_segment }}@if ($visitor->sub_business_segment)
                                        <span class="text-zinc-400"> / {{ $visitor->sub_business_segment }}</span>
                                    @endif
                                </flux:text>
                            </div>
                        @endif
                        @if ($visitor->source)
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Source</flux:text>
                                <flux:text>{{ $visitor->source }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            {{-- All Members --}}
            @php $additionalPersons = $visitor->additional_persons ?? []; @endphp
            @php $totalPersons = 1 + count($additionalPersons); @endphp
            <flux:card>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Members</flux:heading>
                        <flux:badge color="zinc" size="sm">{{ $totalPersons }}
                            {{ Str::plural('person', $totalPersons) }}</flux:badge>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                            <span
                                class="flex items-center justify-center w-7 h-7 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 shrink-0">1</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $visitor->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $visitor->phone_number }} · Primary</p>
                            </div>
                        </div>
                        @foreach ($additionalPersons as $index => $person)
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                                <span
                                    class="flex items-center justify-center w-7 h-7 text-xs font-semibold rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 shrink-0">{{ $index + 2 }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $person['name'] ?? '' }}</p>
                                    @if (!empty($person['phone_number']))
                                        <p class="text-xs text-zinc-500">{{ $person['phone_number'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>

            {{-- Payment Details --}}
            @if ($visitor->payment_amount)
                <flux:card>
                    <div class="p-6">
                        <flux:heading size="lg" class="mb-4">Payment</flux:heading>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Amount</flux:text>
                                <flux:text class="font-mono font-semibold text-lg">
                                    ₹{{ number_format($visitor->payment_amount, 2) }}
                                </flux:text>
                            </div>
                            @if ($visitor->payment_status)
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Status</flux:text>
                                    <flux:text>{{ $visitor->payment_status }}</flux:text>
                                </div>
                            @endif
                            @if ($visitor->payment_method)
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Method</flux:text>
                                    <flux:text>{{ $visitor->payment_method }}</flux:text>
                                </div>
                            @endif
                            @if ($visitor->payment_transaction_id)
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Transaction ID
                                    </flux:text>
                                    <flux:text class="font-mono text-sm">{{ $visitor->payment_transaction_id }}
                                    </flux:text>
                                </div>
                            @endif
                            @if ($visitor->payment_tracking_id)
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Tracking ID</flux:text>
                                    <flux:text class="font-mono text-sm">{{ $visitor->payment_tracking_id }}
                                    </flux:text>
                                </div>
                            @endif
                            @if ($visitor->payment_bank_ref_no)
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Bank Ref No.</flux:text>
                                    <flux:text class="font-mono text-sm">{{ $visitor->payment_bank_ref_no }}
                                    </flux:text>
                                </div>
                            @endif
                            @if ($visitor->payment_completed_at)
                                <div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Paid At</flux:text>
                                    <flux:text>{{ $visitor->payment_completed_at->format('M d, Y H:i') }}</flux:text>
                                </div>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <flux:card>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">Status</flux:heading>
                        <flux:badge color="{{ $visitor->status->color() }}" size="sm">
                            {{ $visitor->status->label() }}
                        </flux:badge>
                    </div>
                    <flux:separator />
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Registration Code</flux:text>
                        <flux:text class="font-mono font-semibold">{{ $visitor->registration_code }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Registered</flux:text>
                        <flux:text>{{ $visitor->created_at->format('M d, Y · H:i') }}</flux:text>
                    </div>
                    @if ($visitor->updated_at->ne($visitor->created_at))
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Last Updated</flux:text>
                            <flux:text>{{ $visitor->updated_at->format('M d, Y · H:i') }}</flux:text>
                        </div>
                    @endif
                    @if ($visitor->payment_notes)
                        <flux:separator />
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Payment Notes</flux:text>
                            <flux:text class="text-sm">{{ $visitor->payment_notes }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Mark as Paid Confirmation Modal --}}
    <flux:modal wire:model="showMarkPaidModal">
        <div class="space-y-6">
            <flux:heading size="lg">Manually Confirm Payment</flux:heading>
            <flux:text>
                Use this only when the payment was debited from the visitor's account but our system did not receive
                confirmation. A WhatsApp pass will be sent after confirming.
            </flux:text>
            <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <flux:text class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>{{ $visitor->name }}</strong> — {{ $visitor->registration_code }}<br>
                    Amount: <strong>₹{{ number_format($visitor->payment_amount, 2) }}</strong>
                </flux:text>
            </div>
            <flux:field>
                <flux:label>Transaction / Tracking ID <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                <flux:input wire:model="manualTransactionId" placeholder="e.g. 123456789 from CCAvenue" />
            </flux:field>
            <flux:field>
                <flux:label>Notes <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                <flux:textarea wire:model="manualNotes" placeholder="Any additional details about this payment..." rows="2" />
            </flux:field>
            <div class="flex gap-2">
                <flux:button variant="primary" wire:click="markPaymentCompleted" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="markPaymentCompleted">Confirm Payment</span>
                    <span wire:loading wire:target="markPaymentCompleted">Processing...</span>
                </flux:button>
                <flux:button variant="ghost" wire:click="$set('showMarkPaidModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <flux:heading size="lg">Delete Visitor</flux:heading>
            <flux:text>
                Are you sure you want to delete this visitor registration? This action cannot be undone.
            </flux:text>
            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="deleteVisitor">Delete</flux:button>
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
