<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Dashboard</flux:heading>

        @if (! auth()->user()->isExhibitor() && $this->exhibitions->isNotEmpty())
            <flux:select
                wire:model.live="exhibitionId"
                variant="listbox"
                searchable
                placeholder="All Exhibitions"
                class="md:max-w-xs"
            >
                <x-slot name="empty"></x-slot>
                <flux:select.option value="">All Exhibitions</flux:select.option>
                @foreach ($this->exhibitions as $exhibitionOption)
                    <flux:select.option :value="(string) $exhibitionOption->id">{{ $exhibitionOption->title }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    {{-- Stats Grid --}}
    @if (auth()->user()->isExhibitor())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Leads --}}
            <a href="{{ route('exhibitor.leads', ['activeTab' => 'list']) }}" wire:navigate
                class="block transition-transform hover:scale-105">
                <flux:card class="h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Leads Captured</flux:text>
                            <flux:heading size="2xl" class="mt-2">{{ $leadsCount }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500">From QR code scans</flux:text>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg dark:bg-blue-900/20">
                            <flux:icon.bookmark class="text-blue-600 size-6 dark:text-blue-400" variant="outline" />
                        </div>
                    </div>
                </flux:card>
            </a>

            {{-- WhatsApp Inquiries --}}
            <a href="{{ route('exhibitor.leads', ['activeTab' => 'whatsapp']) }}" wire:navigate
                class="block transition-transform hover:scale-105">
                <flux:card class="h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">WhatsApp Inquiries</flux:text>
                            <flux:heading size="2xl" class="mt-2">{{ $whatsAppInquiriesCount }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500">From QR code scans</flux:text>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg dark:bg-green-900/20">
                            <flux:icon.chat-bubble-left-ellipsis class="text-green-600 size-6 dark:text-green-400" variant="outline" />
                        </div>
                    </div>
                </flux:card>
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Available Stalls --}}
            <a href="{{ route('admin.inquiries.index', ['tab' => 'all']) }}" wire:navigate
                class="block transition-transform hover:scale-105">
                <flux:card class="h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Available Stalls</flux:text>
                            <flux:heading size="2xl" class="mt-2">{{ $availableStalls }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500">Ready for booking</flux:text>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg dark:bg-green-900/20">
                            <flux:icon.check-circle class="text-green-600 size-6 dark:text-green-400" variant="outline" />
                        </div>
                    </div>
                </flux:card>
            </a>

            {{-- Payments Due Soon --}}
            <a href="{{ route('admin.inquiries.index', ['tab' => 'payment_pending']) }}" wire:navigate
                class="block transition-transform hover:scale-105">
                <flux:card class="h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Payments Due Soon</flux:text>
                            <flux:heading size="2xl" class="mt-2">{{ $paymentsDue }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500">Due within 3 days</flux:text>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-lg dark:bg-orange-900/20">
                            <flux:icon.clock class="text-orange-600 size-6 dark:text-orange-400" variant="outline" />
                        </div>
                    </div>
                </flux:card>
            </a>

            {{-- Confirmed Bookings --}}
            <a href="{{ route('admin.inquiries.index', ['tab' => 'payment_completed']) }}" wire:navigate
                class="block transition-transform hover:scale-105">
                <flux:card class="h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Confirmed Bookings</flux:text>
                            <flux:heading size="2xl" class="mt-2">{{ $bookedStalls }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500">Payment completed</flux:text>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg dark:bg-blue-900/20">
                            <flux:icon.document-check class="text-blue-600 size-6 dark:text-blue-400" variant="outline" />
                        </div>
                    </div>
                </flux:card>
            </a>

            {{-- Pending Reviews --}}
            <a href="{{ route('admin.inquiries.index', ['tab' => 'pending_approval']) }}" wire:navigate
                class="block transition-transform hover:scale-105">
                <flux:card class="h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Pending Reviews</flux:text>
                            <flux:heading size="2xl" class="mt-2">{{ $pendingReviews }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500">Awaiting approval</flux:text>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-lg dark:bg-yellow-900/20">
                            <flux:icon.exclamation-circle class="text-yellow-600 size-6 dark:text-yellow-400"
                                variant="outline" />
                        </div>
                    </div>
                </flux:card>
            </a>

            {{-- Visitors Registered --}}
            <flux:card class="h-full">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Visitors Registered</flux:text>
                        <flux:heading size="2xl" class="mt-2">{{ $visitorsTotal }}</flux:heading>
                        <flux:text class="mt-1 text-xs text-zinc-500">{{ $visitorsToday }} today</flux:text>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg dark:bg-purple-900/20">
                        <flux:icon.users class="text-purple-600 size-6 dark:text-purple-400" variant="outline" />
                    </div>
                </div>
            </flux:card>

            {{-- Visitor Payments Collected --}}
            <flux:card class="h-full">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Visitor Payments Collected</flux:text>
                        <flux:heading size="2xl" class="mt-2">{{ $visitorPaymentTotal }}</flux:heading>
                        <flux:text class="mt-1 text-xs text-zinc-500">{{ $visitorPaymentToday }} today</flux:text>
                    </div>
                    <div class="p-3 bg-teal-100 rounded-lg dark:bg-teal-900/20">
                        <flux:icon.banknotes class="text-teal-600 size-6 dark:text-teal-400" variant="outline" />
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Entry Scans Widget (admin only) --}}
    @if (!auth()->user()->isExhibitor())
        <a href="{{ route('admin.scans.index') }}" wire:navigate class="block">
            <flux:card class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:heading size="sm">Entry Scans — Last 7 Days</flux:heading>
                        <flux:text class="text-xs text-zinc-500 mt-0.5">
                            {{ number_format($scanStats['total_entered']) }} total &middot;
                            {{ number_format($scanStats['today']) }} today &middot;
                            <span class="text-green-600 dark:text-green-400">{{ number_format($scanStats['inside']) }} inside now</span>
                        </flux:text>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg dark:bg-green-900/20">
                        <flux:icon.shield-check class="size-6 text-green-600 dark:text-green-400" variant="outline" />
                    </div>
                </div>
                {{-- Mini bar chart --}}
                <flux:chart :value="$scanStats['daily']" class="h-14">
                    <flux:chart.svg gutter="4">
                        <flux:chart.bar field="entries" class="text-green-500 dark:text-green-400" radius="2" width="75%" />
                    </flux:chart.svg>
                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" />
                        <flux:chart.tooltip.value field="entries" label="Entries" />
                    </flux:chart.tooltip>
                </flux:chart>
            </flux:card>
        </a>
    @endif

    {{-- Quick Actions or Additional Content --}}
    {{-- <flux:card>
        <div class="p-8 text-center">
            <flux:heading size="lg" class="mb-2">Exhibition Management</flux:heading>
            <flux:text class="mb-4 text-zinc-500">
                Manage your exhibition bookings, stalls, and payments from here.
            </flux:text>
            <div class="flex justify-center gap-3">
                <flux:button variant="primary" href="{{ route('admin.inquiries.index') }}" wire:navigate
                    icon="clipboard-document-list" iconVariant="outline">
                    View All Inquiries
                </flux:button>
            </div>
        </div>
    </flux:card> --}}
</div>
