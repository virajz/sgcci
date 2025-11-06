<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Booking #{{ $booking->booking_code }}</flux:heading>
            <flux:subheading class="mt-2">
                Submitted {{ $booking->created_at->diffForHumans() }}
            </flux:subheading>
        </div>

        <flux:badge :color="$booking->status->color()" variant="solid" size="lg">
            {{ $booking->status->label() }}
        </flux:badge>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Contact Information --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Contact Information</flux:heading>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:subheading class="mb-1 text-sm">Brand / Dealership</flux:subheading>
                        <flux:text class="font-semibold">{{ $booking->brand_name }}</flux:text>
                    </div>
                    @if ($booking->facia_name)
                        <div>
                            <flux:subheading class="mb-1 text-sm">Facia Name</flux:subheading>
                            <flux:text class="font-semibold">{{ $booking->facia_name }}</flux:text>
                        </div>
                    @endif
                    @if ($booking->trophy_name)
                        <div>
                            <flux:subheading class="mb-1 text-sm">Trophy Name</flux:subheading>
                            <flux:text class="font-semibold">{{ $booking->trophy_name }}</flux:text>
                        </div>
                    @endif
                    <div>
                        <flux:subheading class="mb-1 text-sm">Contact Person</flux:subheading>
                        <flux:text class="font-semibold">{{ $booking->contact_person }}</flux:text>
                    </div>
                    <div>
                        <flux:subheading class="mb-1 text-sm">Email</flux:subheading>
                        <flux:text class="font-semibold">{{ $booking->email }}</flux:text>
                    </div>
                    <div>
                        <flux:subheading class="mb-1 text-sm">Phone</flux:subheading>
                        <flux:text class="font-semibold">{{ $booking->phone_code }} {{ $booking->phone_number }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:subheading class="mb-1 text-sm">City</flux:subheading>
                        <flux:text class="font-semibold">{{ $booking->city }}</flux:text>
                    </div>
                    @if ($booking->gst_number)
                        <div>
                            <flux:subheading class="mb-1 text-sm">GST Number</flux:subheading>
                            <flux:text class="font-mono font-semibold">{{ $booking->gst_number }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Product Profile --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Product Profile</flux:heading>
                <div class="flex flex-wrap gap-2">
                    @foreach ($booking->product_profile as $profile)
                        <flux:badge variant="outline">{{ ucwords(str_replace('-', ' ', $profile)) }}</flux:badge>
                    @endforeach
                </div>
            </flux:card>

            {{-- Stall Details & Pricing --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Selected Stalls & Pricing</flux:heading>

                <div class="mb-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-zinc-700">
                                <th class="px-4 py-3 font-semibold text-left">Stall Number</th>
                                <th class="px-4 py-3 font-semibold text-right">Area (sq m)</th>
                                <th class="px-4 py-3 font-semibold text-right">Rate/sq m</th>
                                <th class="px-4 py-3 font-semibold text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Models\Booking::getStallLineItems($booking->selected_stalls) as $item)
                                <tr class="border-b dark:border-zinc-700">
                                    <td class="px-4 py-3 font-medium">{{ $item['stall_number'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $item['area'] }}</td>
                                    <td class="px-4 py-3 text-right">₹750.00</td>
                                    <td class="px-4 py-3 font-medium text-right">
                                        ₹{{ number_format($item['price'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 mt-4 space-y-3 border-t dark:border-zinc-700">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Total Area:</span>
                        <span class="font-medium">{{ $booking->total_area }} sq m</span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Subtotal:</span>
                        <span class="font-medium">₹{{ number_format((float) $booking->total_price, 2) }}</span>
                    </div>

                    @if ($booking->discount_percentage > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Discount
                                ({{ number_format((float) $booking->discount_percentage, 2) }}%):</span>
                            <span
                                class="font-medium text-green-600 dark:text-green-400">-₹{{ number_format((float) $booking->discount_amount, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">GST (18%):</span>
                        <span class="font-medium">₹{{ number_format((float) $booking->gst_amount, 2) }}</span>
                    </div>

                    <flux:separator />

                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold">Total Amount:</span>
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            ₹{{ number_format((float) $booking->total_with_gst, 2) }}
                        </span>
                    </div>

                    {{-- Payment Status Section --}}
                    @if (
                        $booking->amount_paid > 0 ||
                            $booking->status === \App\BookingStatus::PaymentPending ||
                            $booking->status === \App\BookingStatus::Allotted)
                        <flux:separator />

                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Amount Paid:</span>
                                <span class="font-semibold text-green-600 dark:text-green-400">
                                    ₹{{ number_format((float) $booking->amount_paid, 2) }}
                                </span>
                            </div>

                            @if ($booking->remaining_amount > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Remaining Amount:</span>
                                    <span class="font-semibold text-orange-600 dark:text-orange-400">
                                        ₹{{ number_format((float) $booking->remaining_amount, 2) }}
                                    </span>
                                </div>

                                @if ($booking->partial_payment_deadline)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Payment Deadline:</span>
                                        <span
                                            class="font-medium {{ $booking->isPaymentOverdue() ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                            {{ $booking->partial_payment_deadline->format('M d, Y') }}
                                            @if ($booking->isPaymentOverdue())
                                                <flux:badge color="red" size="sm" class="ml-1">Overdue
                                                </flux:badge>
                                            @elseif ($booking->isPaymentDeadlineApproaching())
                                                <flux:badge color="orange" size="sm" class="ml-1">Due Soon
                                                </flux:badge>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @endif

                            {{-- Payment Progress Bar --}}
                            <div>
                                <div class="flex items-center justify-between mb-1 text-xs">
                                    <span class="text-zinc-600 dark:text-zinc-400">Payment Progress</span>
                                    <span class="font-medium">{{ round($booking->getPaymentPercentage(), 1) }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-full transition-all {{ $booking->isPaymentCompleted() ? 'bg-green-500' : 'bg-blue-500' }}"
                                        style="width: {{ min(100, $booking->getPaymentPercentage()) }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Payment History --}}
            @if ($booking->payment_history && count($booking->payment_history) > 0)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Payment History</flux:heading>
                    <div class="space-y-3">
                        @foreach ($booking->payment_history as $payment)
                            <div class="pb-3 border-b last:border-0 dark:border-zinc-700">
                                <div class="flex items-start justify-between mb-1">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <flux:badge variant="solid" color="green" size="sm">
                                                ₹{{ number_format($payment['amount'], 2) }}
                                            </flux:badge>
                                            <flux:badge variant="outline" size="sm">
                                                {{ ucwords(str_replace('_', ' ', $payment['method'])) }}
                                            </flux:badge>
                                        </div>
                                        @if (isset($payment['transaction_id']) && $payment['transaction_id'])
                                            <flux:text class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                Ref: {{ $payment['transaction_id'] }}
                                            </flux:text>
                                        @endif
                                        @if (isset($payment['notes']) && $payment['notes'])
                                            <flux:text class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $payment['notes'] }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ \Carbon\Carbon::parse($payment['recorded_at'])->format('M d, Y h:i A') }}
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            {{-- Additional Information --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Additional Information</flux:heading>
                <div class="space-y-4">
                    <div>
                        <flux:subheading class="mb-2 text-sm">Previous Participation</flux:subheading>
                        @if ($booking->has_exhibited_before && count($booking->participation_years) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($booking->participation_years as $year)
                                    <flux:badge variant="outline">{{ $year }}</flux:badge>
                                @endforeach
                            </div>
                        @else
                            <flux:text class="text-zinc-500">First time exhibitor</flux:text>
                        @endif
                    </div>

                    <div>
                        <flux:subheading class="mb-2 text-sm">SGCCI Membership</flux:subheading>
                        @if ($booking->is_sgcci_member && $booking->membership_type)
                            <flux:badge variant="solid" color="blue">
                                {{ ucwords(str_replace('-', ' ', $booking->membership_type)) }}
                            </flux:badge>
                        @else
                            <flux:text class="text-zinc-500">Not a member</flux:text>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Actions</flux:heading>

                {{-- Edit Booking Button (Available to all admins, only if no payment received) --}}
                @if ($booking->amount_paid <= 0)
                    <div class="mb-4">
                        <flux:button href="{{ route('admin.inquiries.edit', $booking) }}" variant="outline"
                            class="w-full" icon="pencil" iconVariant="outline">
                            Edit Booking Details
                        </flux:button>
                    </div>

                    <flux:separator class="my-4" />
                @else
                    <flux:callout variant="info" class="mb-4">
                        <flux:text class="text-sm">Booking cannot be edited as payment has been received.</flux:text>
                    </flux:callout>
                @endif

                @if ($booking->status === \App\BookingStatus::PendingApproval && auth()->user()->isAdmin())
                    <div class="space-y-3">
                        @if (auth()->user()->isSuperAdmin())
                            <flux:callout variant="warning" class="mb-4">
                                This booking is pending initial admin verification. Admins should verify first.
                            </flux:callout>
                        @else
                            <flux:button wire:click="approve" variant="primary" class="w-full" icon="check"
                                iconVariant="outline" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="approve">Verify & Approve</span>
                                <span wire:loading wire:target="approve">Processing...</span>
                            </flux:button>
                        @endif
                    </div>
                @elseif ($booking->status === \App\BookingStatus::ApprovedByAdmin && auth()->user()->isSuperAdmin())
                    <div class="space-y-3">
                        <flux:callout variant="info" class="mb-4">
                            This booking has been verified by an admin. You can now allot the stalls.
                        </flux:callout>

                        <flux:button wire:click="approve" variant="primary" class="w-full" icon="check"
                            iconVariant="outline" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="approve">Approve & Send Payment Link</span>
                            <span wire:loading wire:target="approve">Processing...</span>
                        </flux:button>

                        <flux:button wire:click="openRejectModal" variant="danger" class="w-full" icon="x-mark"
                            iconVariant="outline">
                            Reject Booking
                        </flux:button>
                    </div>
                @elseif ($booking->status === \App\BookingStatus::PaymentPending && auth()->user()->isAdmin())
                    <div class="space-y-3">
                        <flux:callout variant="warning" class="mb-4">
                            Payment link has been sent. Waiting for customer payment confirmation.
                        </flux:callout>

                        <livewire:admin.inquiries.record-payment :booking="$booking" :key="'record-payment-' . $booking->id" />

                        <flux:button x-data
                            x-on:click="navigator.clipboard.writeText('{{ $booking->payment_link }}').then(() => {
                                $flux.toast({
                                    variant: 'success',
                                    heading: 'Copied!',
                                    text: 'Payment link copied to clipboard'
                                });
                            })"
                            variant="outline" class="w-full" icon="link" iconVariant="outline">
                            Copy Payment Link
                        </flux:button>

                        <flux:button wire:click="resendPaymentLink" variant="outline" class="w-full"
                            icon="paper-airplane" iconVariant="outline" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="resendPaymentLink">Resend Payment Link</span>
                            <span wire:loading wire:target="resendPaymentLink">Sending...</span>
                        </flux:button>
                    </div>
                @elseif ($booking->status === \App\BookingStatus::ApprovedByAdmin && !auth()->user()->isSuperAdmin())
                    <flux:callout variant="info">
                        Awaiting super admin approval for stall allotment.
                    </flux:callout>
                @elseif ($booking->status === \App\BookingStatus::Expired && auth()->user()->isAdmin())
                    <div class="space-y-3">
                        <flux:callout variant="danger" class="mb-4">
                            This booking has expired due to payment timeout. You can re-enable it to give the customer
                            another chance.
                        </flux:callout>

                        <flux:button wire:click="reEnableExpiredBooking" variant="primary" class="w-full"
                            icon="arrow-path" iconVariant="outline" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="reEnableExpiredBooking">Re-enable Booking</span>
                            <span wire:loading wire:target="reEnableExpiredBooking">Re-enabling...</span>
                        </flux:button>
                    </div>
                @elseif (in_array($booking->status, [\App\BookingStatus::Cancelled, \App\BookingStatus::Rejected]) &&
                        auth()->user()->isAdmin())
                    <div class="space-y-3">
                        <flux:callout variant="warning" class="mb-4">
                            @if ($booking->status === \App\BookingStatus::Cancelled)
                                This booking has been cancelled. You can re-enable it to give the customer another
                                chance.
                            @else
                                This booking has been rejected. You can re-enable it to give the customer another
                                chance.
                            @endif
                        </flux:callout>

                        <flux:button wire:click="reEnableExpiredBooking" variant="primary" class="w-full"
                            icon="arrow-path" iconVariant="outline" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="reEnableExpiredBooking">Re-enable Booking</span>
                            <span wire:loading wire:target="reEnableExpiredBooking">Re-enabling...</span>
                        </flux:button>
                    </div>
                @elseif (in_array($booking->status, [\App\BookingStatus::Allotted, \App\BookingStatus::PaymentCompleted]) &&
                        auth()->user()->isAdmin())
                    <div class="space-y-3">
                        <flux:callout variant="info" class="mb-4">
                            @if ($booking->status === \App\BookingStatus::Allotted)
                                Stalls have been allotted and payment has been completed.
                            @else
                                Payment completed successfully.
                            @endif
                        </flux:callout>

                        @if ($booking->amount_paid > 0 && auth()->user()->isSuperAdmin())
                            <flux:button wire:click="openRefundModal" variant="danger" class="w-full"
                                icon="receipt-refund" iconVariant="outline">
                                Refund Payment
                            </flux:button>
                        @endif

                        <flux:button wire:click="openReleaseModal" variant="outline" class="w-full" icon="lock-open"
                            iconVariant="outline">
                            Release Stalls
                        </flux:button>
                    </div>
                @else
                    <flux:callout variant="info">
                        @if ($booking->status === \App\BookingStatus::Refunded)
                            This booking has been refunded and stalls released.
                        @else
                            No actions available at this stage.
                        @endif
                    </flux:callout>
                @endif

                {{-- Release Stalls - Available to any admin, for any non-cancelled/non-refunded status --}}
                @if (auth()->user()->isAdmin() &&
                        !in_array($booking->status, [
                            \App\BookingStatus::Cancelled,
                            \App\BookingStatus::Refunded,
                            \App\BookingStatus::Rejected,
                        ]) &&
                        !in_array($booking->status, [\App\BookingStatus::Allotted, \App\BookingStatus::PaymentCompleted]))
                    <flux:separator class="my-4" />
                    <flux:button wire:click="openReleaseModal" variant="outline" class="w-full" icon="lock-open"
                        iconVariant="outline">
                        Release Stalls
                    </flux:button>
                @endif
            </flux:card>

            {{-- Approval History --}}
            @if ($booking->admin_approved_at || $booking->super_admin_approved_at || $booking->rejected_at || $booking->refunded_at)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Approval History</flux:heading>

                    <div class="space-y-3">
                        @if ($booking->admin_approved_at)
                            <div class="pb-3 border-b dark:border-zinc-700">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:icon.check class="w-4 h-4 text-green-500" />
                                    <flux:subheading class="text-sm">Admin Approved</flux:subheading>
                                </div>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $booking->adminApprovedBy->name }}
                                </flux:text>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ $booking->admin_approved_at->format('M d, Y h:i A') }}
                                </flux:text>
                            </div>
                        @endif

                        @if ($booking->super_admin_approved_at)
                            <div class="pb-3 border-b dark:border-zinc-700">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:icon.check class="w-4 h-4 text-green-500" />
                                    <flux:subheading class="text-sm">Payment Link Sent by Super Admin</flux:subheading>
                                </div>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $booking->superAdminApprovedBy->name }}
                                </flux:text>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ $booking->super_admin_approved_at->format('M d, Y h:i A') }}
                                </flux:text>
                            </div>
                        @endif

                        @if ($booking->payment_completed_at)
                            <div class="pb-3 border-b dark:border-zinc-700">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:icon.check class="w-4 h-4 text-green-500" />
                                    <flux:subheading class="text-sm">Payment Completed & Stalls Allotted
                                    </flux:subheading>
                                </div>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ $booking->payment_completed_at->format('M d, Y h:i A') }}
                                </flux:text>
                            </div>
                        @endif

                        @if ($booking->rejected_at)
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:icon.x-mark class="w-4 h-4 text-red-500" />
                                    <flux:subheading class="text-sm">Rejected</flux:subheading>
                                </div>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $booking->rejectedBy->name }}
                                </flux:text>
                                <flux:text class="mb-2 text-xs text-zinc-500">
                                    {{ $booking->rejected_at->format('M d, Y h:i A') }}
                                </flux:text>
                                @if ($booking->rejection_reason)
                                    <flux:callout variant="danger" class="mt-2">
                                        <flux:text class="text-sm">{{ $booking->rejection_reason }}</flux:text>
                                    </flux:callout>
                                @endif
                            </div>
                        @endif

                        @if ($booking->refunded_at)
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:icon.receipt-refund class="w-4 h-4 text-purple-500" />
                                    <flux:subheading class="text-sm">Refunded & Stalls Released</flux:subheading>
                                </div>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $booking->refundedBy->name }}
                                </flux:text>
                                <flux:text class="mb-2 text-xs text-zinc-500">
                                    {{ $booking->refunded_at->format('M d, Y h:i A') }}
                                </flux:text>
                                @if ($booking->refund_reason)
                                    <flux:callout variant="warning" class="mt-2">
                                        <flux:text class="text-sm">{{ $booking->refund_reason }}</flux:text>
                                    </flux:callout>
                                @endif
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- Payment Information --}}
            @if ($booking->payment_link)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Payment Information</flux:heading>

                    <div class="space-y-3">
                        @if ($booking->payment_due_at)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <flux:subheading class="text-sm">Payment Due Date</flux:subheading>
                                    @if ($booking->payment_due_at->isPast() && $booking->status === \App\BookingStatus::PaymentPending)
                                        <flux:badge color="red" variant="solid" size="sm">OVERDUE
                                        </flux:badge>
                                    @endif
                                </div>
                                <flux:text class="font-semibold"
                                    :class="$booking->payment_due_at->isPast() && $booking->status === \App\BookingStatus::PaymentPending ? 'text-red-600 dark:text-red-400' : ''">
                                    {{ $booking->payment_due_at->format('M d, Y') }}
                                </flux:text>
                                <flux:text class="text-xs"
                                    :class="$booking->payment_due_at->isPast() && $booking->status === \App\BookingStatus::PaymentPending ? 'text-red-500' : 'text-zinc-500'">
                                    ({{ $booking->payment_due_at->diffForHumans() }})
                                </flux:text>
                            </div>
                        @endif

                        @if ($booking->payment_link_sent_at)
                            <div>
                                <flux:subheading class="mb-1 text-sm">Link Sent</flux:subheading>
                                <flux:text class="text-sm">
                                    {{ $booking->payment_link_sent_at->format('M d, Y h:i A') }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Reject Modal --}}
    <flux:modal wire:model="showRejectModal" variant="flyout">
        <form wire:submit="reject">
            <flux:heading size="lg" class="mb-4">Reject Booking</flux:heading>

            <flux:subheading class="mb-4">
                Please provide a reason for rejecting this booking. This will be logged for future reference.
            </flux:subheading>

            <flux:field>
                <flux:label>Rejection Reason</flux:label>
                <flux:textarea wire:model="rejectionReason" rows="4"
                    placeholder="Enter the reason for rejection..." />
                <flux:error name="rejectionReason" />
            </flux:field>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button type="button" variant="ghost" wire:click="$set('showRejectModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="reject">Confirm Rejection</span>
                    <span wire:loading wire:target="reject">Rejecting...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal" variant="flyout">
        <form wire:submit="refundAndRelease">
            <flux:heading size="lg" class="mb-4">Refund Payment</flux:heading>

            <flux:subheading class="mb-4">
                This action will mark the booking as refunded and release the stalls for other customers. Please provide
                a reason for this refund.
            </flux:subheading>

            <flux:callout variant="warning" class="mb-4">
                <flux:text class="text-sm font-semibold">
                    Amount to Refund: ₹{{ number_format($booking->amount_paid, 2) }}
                </flux:text>
            </flux:callout>

            <flux:field>
                <flux:label>Refund Reason</flux:label>
                <flux:textarea wire:model="refundReason" rows="4"
                    placeholder="Enter the reason for refund (e.g., customer request, event cancelled)..." />
                <flux:error name="refundReason" />
            </flux:field>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button type="button" variant="ghost" wire:click="$set('showRefundModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="refundAndRelease">Confirm Refund</span>
                    <span wire:loading wire:target="refundAndRelease">Processing...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Release Stalls Modal --}}
    <flux:modal wire:model="showReleaseModal" variant="flyout">
        <form wire:submit="releaseStalls">
            <flux:heading size="lg" class="mb-4">Release Stalls</flux:heading>

            <flux:subheading class="mb-4">
                This action will cancel the booking and release the stalls for other customers. Please provide a reason
                for releasing these stalls.
            </flux:subheading>

            <flux:callout variant="info" class="mb-4">
                <flux:text class="text-sm">
                    <strong>Stalls to be released:</strong> {{ implode(', ', $booking->selected_stalls) }}
                </flux:text>
            </flux:callout>

            <flux:field>
                <flux:label>Release Reason</flux:label>
                <flux:textarea wire:model="releaseReason" rows="4"
                    placeholder="Enter the reason for releasing stalls (e.g., customer cancellation, duplicate booking)..." />
                <flux:error name="releaseReason" />
            </flux:field>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button type="button" variant="ghost" wire:click="$set('showReleaseModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="releaseStalls">Confirm Release</span>
                    <span wire:loading wire:target="releaseStalls">Processing...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
