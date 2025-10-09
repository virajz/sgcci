<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="flex flex-col items-center justify-center min-h-screen antialiased">
    <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="w-full h-auto mb-8 max-w-32">
    <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="w-full h-auto mb-8 max-w-64">
    @isset($exhibition)
        <flux:button href="{{ route('exhibitions.booking.show', $exhibition) }}">
            Book a Stall
        </flux:button>
    @endisset
    @fluxScripts
</body>

</html>
