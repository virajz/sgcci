@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <flux:heading size="xl">Support Ticket Details</flux:heading>
                <flux:text class="mt-1">Ticket Number: <span
                        class="font-mono font-semibold">{{ $ticket->ticket_number }}</span></flux:text>
            </div>
            <flux:button :href="route('admin.support-tickets.index')" variant="ghost" wire:navigate icon="arrow-left"
                iconVariant="micro">
                Back to List
            </flux:button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Booking Information --}}
                <flux:card>
                    <div class="p-6">
                        <flux:heading size="lg" class="mb-4">Booking Information</flux:heading>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Booking Code</flux:text>
                                <flux:text class="font-mono font-semibold">{{ $ticket->booking->booking_code }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Brand Name</flux:text>
                                <flux:text class="font-semibold">{{ $ticket->booking->brand_name }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Contact Person</flux:text>
                                <flux:text>{{ $ticket->booking->contact_person }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Email</flux:text>
                                <flux:text>{{ $ticket->booking->email }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Booking Status</flux:text>
                                <flux:badge :color="$ticket->booking->status->color()">
                                    {{ $ticket->booking->status->label() }}</flux:badge>
                            </div>
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Amount</flux:text>
                                <flux:text class="font-semibold">
                                    ₹{{ number_format($ticket->booking->total_with_gst, 2) }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:card>

                {{-- Uploaded Documents --}}
                <flux:card>
                    <div class="p-6">
                        <flux:heading size="lg" class="mb-4">Uploaded Documents
                            ({{ count($ticket->documents ?? []) }})</flux:heading>

                        @if (empty($ticket->documents))
                            <flux:text class="text-zinc-500 dark:text-zinc-400">No documents uploaded</flux:text>
                        @else
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($ticket->documents as $index => $document)
                                    @php
                                        $extension = pathinfo($document, PATHINFO_EXTENSION);
                                        $isPdf = strtolower($extension) === 'pdf';
                                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png']);
                                        $filename = basename($document);
                                    @endphp

                                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                        @if ($isImage)
                                            <a href="{{ Storage::url($document) }}" target="_blank" class="block group">
                                                <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                                    <img src="{{ Storage::url($document) }}"
                                                        alt="Document {{ $index + 1 }}"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                                </div>
                                                <div class="p-3">
                                                    <flux:text class="text-sm truncate">{{ $filename }}</flux:text>
                                                </div>
                                            </a>
                                        @elseif ($isPdf)
                                            <a href="{{ Storage::url($document) }}" target="_blank"
                                                class="block group">
                                                <div
                                                    class="aspect-video bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                                    <flux:icon.document-text class="w-16 h-16 text-red-600" />
                                                </div>
                                                <div class="p-3">
                                                    <flux:text class="text-sm truncate">{{ $filename }}</flux:text>
                                                </div>
                                            </a>
                                        @endif

                                        <div class="flex gap-2 p-3 border-t border-zinc-200 dark:border-zinc-700">
                                            <flux:button size="sm" variant="outline"
                                                href="{{ Storage::url($document) }}" target="_blank" class="flex-1"
                                                icon="eye" iconVariant="micro">
                                                View
                                            </flux:button>
                                            <flux:button size="sm" variant="outline"
                                                href="{{ route('support-tickets.download', ['path' => $document]) }}"
                                                class="flex-1" icon="arrow-down-tray" iconVariant="micro">
                                                Download
                                            </flux:button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Status Card --}}
                <flux:card>
                    <div class="p-6">
                        <flux:heading size="lg" class="mb-4">Ticket Status</flux:heading>

                        @php
                            $statusColor = match ($ticket->status) {
                                'pending' => 'yellow',
                                'approved' => 'green',
                                'rejected' => 'red',
                                default => 'gray',
                            };
                        @endphp

                        <div class="mb-4">
                            <flux:badge :color="$statusColor" size="lg">{{ ucfirst($ticket->status) }}
                            </flux:badge>
                        </div>

                        @if ($ticket->reviewed_at)
                            <div class="space-y-2 text-sm">
                                <div>
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">Reviewed By</flux:text>
                                    <flux:text class="font-semibold">{{ $ticket->reviewedBy->name }}</flux:text>
                                </div>
                                <div>
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">Reviewed At</flux:text>
                                    <flux:text>{{ $ticket->reviewed_at->format('M d, Y H:i') }}</flux:text>
                                </div>
                            </div>
                        @endif

                        @if ($ticket->status === 'rejected' && $ticket->rejection_reason)
                            <div class="mt-4 p-3 bg-red-50 dark:bg-red-950 rounded-lg">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Rejection Reason
                                </flux:text>
                                <flux:text class="text-sm">{{ $ticket->rejection_reason }}</flux:text>
                            </div>
                        @endif

                        @if ($ticket->status === 'pending')
                            <flux:separator class="my-4" />

                            <div class="space-y-3">
                                <flux:button wire:click="$set('showApprovalModal', true)" variant="primary"
                                    class="w-full">
                                    Approve Ticket
                                </flux:button>
                                <flux:button wire:click="$set('showRejectionModal', true)" variant="danger"
                                    class="w-full">
                                    Reject Ticket
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Metadata Card --}}
                <flux:card>
                    <div class="p-6">
                        <flux:heading size="lg" class="mb-4">Metadata</flux:heading>
                        <div class="space-y-3 text-sm">
                            <div>
                                <flux:text class="text-zinc-500 dark:text-zinc-400">Created At</flux:text>
                                <flux:text>{{ $ticket->created_at->format('M d, Y H:i') }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-zinc-500 dark:text-zinc-400">Last Updated</flux:text>
                                <flux:text>{{ $ticket->updated_at->format('M d, Y H:i') }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    </div>

    {{-- Approval Modal --}}
    <flux:modal wire:model="showApprovalModal" class="max-w-md">
        <form wire:submit="approve">
            <div class="p-6">
                <flux:heading size="lg" class="mb-4">Approve Support Ticket</flux:heading>
                <flux:text class="mb-6">
                    Are you sure you want to approve this ticket? This will allow the booking to proceed with payment.
                </flux:text>

                <div class="flex gap-3 justify-end">
                    <flux:button type="button" variant="ghost" wire:click="$set('showApprovalModal', false)">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Approve Ticket
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Rejection Modal --}}
    <flux:modal wire:model="showRejectionModal" class="max-w-md">
        <form wire:submit="reject">
            <div class="p-6">
                <flux:heading size="lg" class="mb-4">Reject Support Ticket</flux:heading>

                <flux:field class="mb-6">
                    <flux:label>Rejection Reason</flux:label>
                    <flux:textarea wire:model="rejectionReason" placeholder="Please provide a reason for rejection..."
                        rows="4" />
                    <flux:error name="rejectionReason" />
                </flux:field>

                <div class="flex gap-3 justify-end">
                    <flux:button type="button" variant="ghost" wire:click="$set('showRejectionModal', false)">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="danger">
                        Reject Ticket
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
