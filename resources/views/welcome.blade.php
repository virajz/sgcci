<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="flex flex-col items-center justify-center min-h-screen antialiased">
    <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto mb-8 max-w-32">
    <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto mb-8 max-w-64">
    <div class="flex flex-col w-full max-w-xs gap-4">
        @isset($exhibition)
            <flux:button href="{{ route('visitors-registration', $exhibition) }}" variant="primary" class="w-full">
                Register as Visitor
            </flux:button>
            {{-- <flux:button href="{{ route('support-tickets.create') }}" variant="outline" class="w-full">
                Create Support Ticket
            </flux:button> --}}
        @endisset
    </div>
    @fluxScripts
</body>

</html>
