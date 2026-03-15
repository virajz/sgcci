<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="h-screen flex flex-col overflow-hidden bg-black">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

    <div class="flex-1 overflow-hidden">
        {{ $slot }}
    </div>

    @fluxScripts
</body>

</html>
