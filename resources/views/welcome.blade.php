<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen antialiased bg-zinc-50 dark:bg-zinc-950">
    <flux:main class="w-full max-w-4xl px-4 py-8 mx-auto space-y-8 sm:px-6 sm:py-12">

        {{-- Header --}}
        <section class="flex flex-col items-center space-y-4 text-center">
            <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-auto h-20 sm:h-24">
            <div class="space-y-1">
                <flux:heading size="xl" class="font-bold tracking-tight">Upcoming Exhibitions</flux:heading>
                <flux:subheading>Register as a visitor for any of the exhibitions below.</flux:subheading>
            </div>
        </section>

        {{-- Exhibitions --}}
        @if ($exhibitions->isNotEmpty())
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($exhibitions as $exhibition)
                    <a href="{{ route('visitors-registration', $exhibition) }}"
                        wire:key="exhibition-{{ $exhibition->id }}"
                        class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">

                        {{-- Logo banner --}}
                        <div class="flex items-center justify-center h-40 p-6 bg-zinc-100 dark:bg-zinc-800">
                            @if ($exhibition->logo_url)
                                <img src="{{ $exhibition->logo_url }}" alt="{{ $exhibition->title }} Logo"
                                    class="object-contain max-w-full max-h-full">
                            @else
                                <flux:icon name="building-storefront" class="size-16 text-zinc-400 dark:text-zinc-600" />
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="flex items-start justify-between gap-3 p-5">
                            <div class="space-y-1">
                                <flux:heading size="lg" class="font-semibold">{{ $exhibition->title }}</flux:heading>
                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                    {{ $exhibition->start_date->format('d M') }} – {{ $exhibition->end_date->format('d M Y') }}
                                </flux:text>
                            </div>
                            <flux:icon.arrow-right
                                class="size-5 shrink-0 text-zinc-400 transition group-hover:translate-x-1 group-hover:text-zinc-900 dark:group-hover:text-white" />
                        </div>
                    </a>
                @endforeach
            </section>
        @else
            <section
                class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800">
                <flux:icon name="calendar" class="size-10 text-zinc-400" />
                <flux:heading size="lg">No upcoming exhibitions</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    There are no exhibitions open for registration right now. Please check back soon.
                </flux:text>
            </section>
        @endif

    </flux:main>

    @fluxScripts
</body>

</html>
