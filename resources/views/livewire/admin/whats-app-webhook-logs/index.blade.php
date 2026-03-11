<div class="space-y-6" x-data="{ selected: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">WhatsApp Webhook Logs</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Raw payloads received from the WhatsApp webhook endpoint.</flux:text>
        </div>
        <flux:badge color="zinc" class="font-mono text-xs">POST /webhook/whatsapp</flux:badge>
    </div>

    {{-- Search --}}
    <flux:field class="max-w-sm">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search IP, path, method…"
            icon="magnifying-glass"
            clearable
        />
    </flux:field>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Method</flux:table.column>
            <flux:table.column>IP Address</flux:table.column>
            <flux:table.column>Received At</flux:table.column>
            <flux:table.column>Payload</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($logs as $log)
                <flux:table.row :key="$log->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $log->id }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge
                            color="{{ $log->method === 'POST' ? 'blue' : ($log->method === 'GET' ? 'green' : 'zinc') }}"
                            size="sm"
                        >{{ $log->method }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $log->ip ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                        {{ $log->created_at->format('d M Y, h:i:s A') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($log->payload)
                            <flux:button
                                size="sm"
                                variant="ghost"
                                x-on:click="selected = {{ json_encode([
                                    'payload' => json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                                    'headers' => json_encode($log->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                                    'received_at' => $log->created_at->format('d M Y, h:i:s A'),
                                    'ip' => $log->ip,
                                    'method' => $log->method,
                                ]) }}; $flux.modal('payload-detail').show()"
                            >
                                View payload
                                <flux:icon name="arrow-top-right-on-square" class="size-3" />
                            </flux:button>
                        @else
                            <span class="text-xs text-zinc-400">Empty</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-12 text-zinc-400">
                        No webhook logs found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $logs->links() }}

    {{-- Payload detail flyout --}}
    <flux:modal name="payload-detail" flyout position="right" class="md:w-2xl" x-on:close="selected = null">
        <template x-if="selected">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Webhook Payload</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                        <span class="font-mono font-semibold" x-text="selected.method"></span>
                        &middot;
                        <span x-text="selected.received_at"></span>
                        &middot;
                        <span class="font-mono text-xs" x-text="selected.ip"></span>
                    </flux:text>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Payload</flux:heading>
                    <pre class="bg-zinc-900 text-green-400 text-xs rounded-lg p-4 overflow-auto max-h-80 whitespace-pre-wrap break-all" x-text="selected.payload"></pre>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Headers</flux:heading>
                    <pre class="bg-zinc-900 text-zinc-400 text-xs rounded-lg p-4 overflow-auto max-h-80 whitespace-pre-wrap break-all" x-text="selected.headers"></pre>
                </div>
            </div>
        </template>
    </flux:modal>

</div>
