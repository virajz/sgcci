<div>
    {{-- Trigger Button --}}
    <flux:button wire:click="openModal" variant="primary" icon="credit-card">
        Record Payment
    </flux:button>

    {{-- Payment Modal --}}
    <flux:modal name="record-payment" :open="$showModal" wire:model="showModal" variant="flyout">
        <form wire:submit="recordPayment">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Record Payment</flux:heading>
                    <flux:subheading>
                        Booking: {{ $booking->booking_code }} | {{ $booking->brand_name }}
                    </flux:subheading>
                </div>

                {{-- Payment Summary --}}
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <flux:subheading class="mb-1">Total Amount</flux:subheading>
                            <flux:text class="font-semibold">₹{{ number_format($booking->total_with_gst, 2) }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Amount Paid</flux:subheading>
                            <flux:text class="font-semibold text-green-600 dark:text-green-400">
                                ₹{{ number_format($booking->amount_paid, 2) }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Remaining Amount</flux:subheading>
                            <flux:text class="font-semibold text-orange-600 dark:text-orange-400">
                                ₹{{ number_format($booking->remaining_amount, 2) }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Payment Progress</flux:subheading>
                            <flux:text class="font-semibold">{{ round($booking->getPaymentPercentage(), 1) }}%
                            </flux:text>
                        </div>
                    </div>
                </div>

                {{-- Payment Type Selection --}}
                <flux:field>
                    <flux:label>Payment Type</flux:label>
                    <flux:radio.group wire:model.live="paymentType" variant="cards">
                        <flux:radio value="partial" label="Partial Payment" description="Accept custom amount" />
                        <flux:radio value="full" label="Full Payment" description="Accept remaining balance" />
                    </flux:radio.group>
                    <flux:error name="paymentType" />
                </flux:field>

                {{-- Payment Amount (only for partial) --}}
                @if ($paymentType === 'partial')
                    <flux:field>
                        <flux:label>Payment Amount (₹)</flux:label>
                        <flux:input wire:model.blur="paymentAmount" type="number" step="0.01" min="0.01"
                            max="{{ $booking->remaining_amount }}" placeholder="Enter amount" />
                        <flux:description>
                            Suggested: 50% = ₹{{ number_format($booking->total_with_gst * 0.5, 2) }}
                            | Maximum: ₹{{ number_format($booking->remaining_amount, 2) }}
                        </flux:description>
                        <flux:error name="paymentAmount" />
                    </flux:field>
                @else
                    <flux:field>
                        <flux:label>Payment Amount (₹)</flux:label>
                        <flux:input :value="number_format($booking->remaining_amount, 2)" disabled />
                        <flux:description>Full remaining balance will be recorded</flux:description>
                    </flux:field>
                @endif

                {{-- Payment Method --}}
                <flux:field>
                    <flux:label>Payment Method</flux:label>
                    <flux:select wire:model="paymentMethod" placeholder="Select payment method" variant="listbox">
                        <flux:select.option value="cash">Cash</flux:select.option>
                        <flux:select.option value="cheque">Cheque</flux:select.option>
                        <flux:select.option value="bank_transfer">Bank Transfer / NEFT / RTGS</flux:select.option>
                        <flux:select.option value="upi">UPI</flux:select.option>
                        <flux:select.option value="card">Card (Debit/Credit)</flux:select.option>
                        <flux:select.option value="other">Other</flux:select.option>
                    </flux:select>
                    <flux:error name="paymentMethod" />
                </flux:field>

                {{-- Transaction ID --}}
                <flux:field>
                    <flux:label>Transaction ID / Reference Number (Optional)</flux:label>
                    <flux:input wire:model="transactionId" placeholder="e.g., CHQ123456, UTR123456" />
                    <flux:error name="transactionId" />
                </flux:field>

                {{-- Notes --}}
                <flux:field>
                    <flux:label>Notes (Optional)</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Any additional notes about this payment"
                        rows="3" />
                    <flux:error name="notes" />
                </flux:field>

                {{-- Actions --}}
                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="closeModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Record Payment
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
