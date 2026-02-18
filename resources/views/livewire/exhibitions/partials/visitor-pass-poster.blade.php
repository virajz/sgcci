<div class="flex flex-col items-center gap-4">
    <div class="w-full max-w-sm mx-auto overflow-hidden rounded-2xl shadow-xl">
        <img src="{{ $passImageDataUri }}" alt="Visitor Pass — {{ $visitorName }}" class="block w-full h-auto">
    </div>

    <flux:button variant="primary" href="{{ $downloadUrl }}" icon="arrow-down-tray" class="w-full max-w-sm">
        Download Pass — {{ $visitorName }}
    </flux:button>
</div>
