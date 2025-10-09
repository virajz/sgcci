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
                </div>
            </flux:card>

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

                        @if (auth()->user()->isSuperAdmin())
                            <flux:button wire:click="markPaymentCompleted" variant="primary" class="w-full"
                                icon="check" iconVariant="outline" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="markPaymentCompleted">Mark Payment as
                                    Completed</span>
                                <span wire:loading wire:target="markPaymentCompleted">Processing...</span>
                            </flux:button>
                        @endif

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
                @else
                    <flux:callout variant="info">
                        @if ($booking->status === \App\BookingStatus::Rejected)
                            This booking has been rejected.
                        @elseif ($booking->status === \App\BookingStatus::Allotted)
                            Stalls have been allotted and payment has been completed.
                        @elseif ($booking->status === \App\BookingStatus::PaymentCompleted)
                            Payment completed successfully.
                        @elseif ($booking->status === \App\BookingStatus::Expired)
                            This booking has expired.
                        @else
                            No actions available at this stage.
                        @endif
                    </flux:callout>
                @endif
            </flux:card>

            {{-- Approval History --}}
            @if ($booking->admin_approved_at || $booking->super_admin_approved_at || $booking->rejected_at)
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
</div>
