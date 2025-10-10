<div class="space-y-6">
    <flux:heading size="xl">Dashboard</flux:heading>

    {{-- Stats Grid --}}
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
    </div>

    {{-- Quick Actions or Additional Content --}}
    <flux:card>
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
    </flux:card>
</div>
