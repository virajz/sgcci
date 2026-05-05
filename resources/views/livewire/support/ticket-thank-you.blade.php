<div class="flex items-center justify-center min-h-screen p-4">
    <flux:main class="w-full max-w-3xl mx-auto">
        <div class="pb-6 mb-6 space-y-8 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto max-w-16">
                @if ($ticket?->booking?->exhibition?->logo_url)
                    <img src="{{ $ticket->booking->exhibition->logo_url }}" alt="{{ $ticket->booking->exhibition->title }} Logo" class="w-full h-auto max-w-32">
                @endif
            </div>

            <div class="text-center">
                <div
                    class="flex items-center justify-center w-20 h-20 mx-auto mb-6 ring-[12px] bg-green-600 rounded-full ring-green-200 dark:ring-green-800">
                    <flux:icon.check variant="outline" class="w-12 h-12 text-white" />
                </div>

                <flux:heading size="xl" class="mb-4 text-3xl">Support Ticket Created!</flux:heading>

                <flux:subheading class="mb-8">
                    Thank you for submitting your support ticket. Our team will review your case and respond shortly.
                </flux:subheading>
            </div>

            <flux:card class="p-8">
                <div class="space-y-6">
                    <div class="text-center">
                        <flux:subheading class="mb-2">Your Ticket Number</flux:subheading>
                        <div
                            class="inline-flex items-center px-8 py-4 text-4xl font-bold tracking-widest border-2 border-dashed rounded-lg bg-zinc-50 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-600">
                            {{ $ticket->ticket_number }}
                        </div>
                        <flux:text class="block mt-2 text-sm text-zinc-500">
                            Please save this ticket number for your records
                        </flux:text>
                    </div>

                    <flux:separator />

                    <div class="space-y-4">
                        <flux:heading size="lg">Ticket Details</flux:heading>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <flux:subheading class="text-sm">Booking Code</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $ticket->booking->booking_code }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:subheading class="text-sm">Brand / Dealership</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $ticket->booking->brand_name }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:subheading class="text-sm">Booking Status</flux:subheading>
                                <flux:badge :color="$ticket->booking->status->color()">
                                    {{ $ticket->booking->status->label() }}
                                </flux:badge>
                            </div>
                            <div>
                                <flux:subheading class="text-sm">Ticket Status</flux:subheading>
                                <flux:badge color="yellow">
                                    {{ ucfirst($ticket->status) }}
                                </flux:badge>
                            </div>
                            <div>
                                <flux:subheading class="text-sm">Submitted Date</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ $ticket->created_at->format('F j, Y') }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:subheading class="text-sm">Documents Uploaded</flux:subheading>
                                <flux:text class="font-semibold text-black dark:text-white">
                                    {{ count($ticket->documents) }} {{ Str::plural('file', count($ticket->documents)) }}
                                </flux:text>
                            </div>
                        </div>

                        <flux:separator />

                        <div>
                            <flux:subheading class="mb-2 text-sm">Uploaded Documents</flux:subheading>
                            <div class="space-y-2">
                                @foreach ($ticket->documents as $document)
                                    <div
                                        class="flex items-center gap-3 p-3 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        @if (Str::endsWith($document['filename'], ['.jpg', '.jpeg', '.png']))
                                            <img src="{{ $document['url'] }}" alt="{{ $document['filename'] }}"
                                                class="object-cover w-12 h-12 rounded">
                                        @else
                                            <flux:icon.document variant="outline" class="w-12 h-12 text-zinc-500" />
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <flux:text class="text-sm font-medium truncate">
                                                {{ $document['filename'] }}
                                            </flux:text>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:callout variant="info">
                <flux:heading size="lg">What's Next?</flux:heading>
                <flux:text class="mt-2">
                    Our support team will review your ticket and the documents you've submitted. We'll contact you via
                    WhatsApp or call you at <strong>{{ $ticket->booking->phone_code }}
                        {{ $ticket->booking->phone_number }}</strong> within 2-3 business days with an update on your
                    case.
                </flux:text>
                <flux:text class="mt-2">
                    Keep your ticket number (<strong>{{ $ticket->ticket_number }}</strong>) handy for any future
                    reference or follow-up inquiries.
                </flux:text>
            </flux:callout>

            <div class="flex justify-center gap-4">
                <flux:button href="{{ route('support-tickets.create') }}" variant="outline" icon="plus"
                    iconVariant="outline">
                    Create Another Ticket
                </flux:button>
                <flux:button href="{{ route('home') }}" variant="primary" icon="home" iconVariant="outline">
                    Go to Homepage
                </flux:button>
            </div>
        </div>
    </flux:main>
</div>
