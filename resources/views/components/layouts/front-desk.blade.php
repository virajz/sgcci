<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="h-screen flex flex-col overflow-hidden bg-zinc-100 dark:bg-zinc-900">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

    {{-- Top bar --}}
    <header class="flex items-center justify-between px-4 h-11 shrink-0 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <div class="flex items-center gap-2">
            <flux:icon name="building-storefront" class="size-4 text-zinc-400" />
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 tracking-tight">Front Desk</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                Sign out
            </button>
        </form>
    </header>

    <div class="flex-1 overflow-hidden">
        {{ $slot }}
    </div>

    @persist('toast')
        <flux:toast />
    @endpersist

    @fluxScripts
</body>

</html>
