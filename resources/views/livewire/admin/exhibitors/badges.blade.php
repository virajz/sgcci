<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.exhibitors.index')" wire:navigate>
            Back to Exhibitors
        </flux:button>
    </div>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $booking->brand_name }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Stalls: {{ implode(', ', $booking->selected_stalls) }} &middot;
                {{ $members->count() }} / {{ $booking->badge_limit }} badges used
            </flux:text>
        </div>

        @if ($members->isNotEmpty())
            <flux:button variant="primary" icon="arrow-down-tray"
                :href="route('exhibitor.badges.download-all', $booking)">
                Download All Badges
            </flux:button>
        @endif
    </div>

    {{-- QR info --}}
    <flux:card class="flex items-start gap-6">
        <div class="flex-shrink-0">
            {!! $qrSvg !!}
        </div>
        <div class="space-y-1">
            <flux:heading size="sm">Exhibitor QR Code</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                This QR code is printed on each badge. When scanned:
            </flux:text>
            <ul class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                <li>• <strong>Admin</strong> → this badges page</li>
                <li>• <strong>Exhibitor</strong> → their own badges page</li>
                <li>• <strong>Visitor / public</strong> → WhatsApp enquiry</li>
            </ul>
            <flux:text class="mt-2 font-mono text-xs text-zinc-400">
                {{ route('exhibitor.scan', $booking->booking_code) }}
            </flux:text>
        </div>
    </flux:card>

    {{-- Badge members grid --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Team Members</flux:heading>

        @if ($members->isEmpty())
            <div class="py-10 text-center">
                <flux:icon.users class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">No badge members added yet.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($members as $member)
                    <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700">
                        {{-- Badge preview --}}
                        <div class="relative bg-zinc-50 dark:bg-zinc-800">
                            <img src="{{ route('exhibitor.badges.inline', [$booking, $member]) }}"
                                alt="Badge — {{ $member->name }}"
                                class="w-full h-auto"
                                loading="lazy">
                        </div>

                        <div class="flex items-center justify-between p-3">
                            <div>
                                <flux:text class="font-semibold">{{ $member->name }}</flux:text>
                            </div>
                            <flux:button size="sm" variant="primary" icon="arrow-down-tray"
                                :href="route('exhibitor.badges.download', [$booking, $member])">
                                Download
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>
</div>
