<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head', ['title' => 'Redirecting to Payment Gateway'])
</head>

<body class="flex items-center justify-center min-h-screen antialiased bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-2xl px-4 mx-auto">
        <flux:card class="p-8">
            {{-- Header Logos --}}
            <div class="flex items-center justify-between mb-8">
                <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="h-auto max-w-12">
                <img src="{{ asset('brand/auto-expo-logo.png') }}" alt="Auto Expo Logo" class="h-auto max-w-24">
            </div>

            {{-- Loading Spinner --}}
            <div class="mb-6 text-center">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-16 h-16 animate-spin text-primary-600 dark:text-primary-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="2"></circle>
                        <path class="opacity-75" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            d="M12 2a10 10 0 0 1 10 10">
                        </path>
                    </svg>
                </div>
            </div>

            {{-- Title --}}
            <flux:heading size="xl" class="mb-2 text-center">Redirecting to Payment Gateway</flux:heading>
            <flux:subheading class="mb-8 text-center">
                Please wait while we securely redirect you to complete your payment...
            </flux:subheading>

            <flux:separator class="my-6" />

            {{-- Registration Summary --}}
            <div class="space-y-4">
                <flux:heading size="lg" class="mb-4">Registration Summary</flux:heading>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Registration Code</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">{{ $visitor->registration_code }}</flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Exhibition</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">
                        {{ $visitor->exhibition->title }}
                    </flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Visitor Name</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">{{ $visitor->name }}</flux:text>
                </div>

                <div class="flex items-center justify-between py-4 pt-6">
                    <flux:heading size="lg">Entry Fee</flux:heading>
                    <flux:heading size="xl" class="text-primary-600 dark:text-primary-400">
                        {{ Number::currency((float) $visitor->payment_amount, 'INR') }}
                    </flux:heading>
                </div>
            </div>

            {{-- Security Message --}}
            <div class="p-4 mt-6 rounded-lg bg-blue-50 dark:bg-blue-950/30">
                <div class="flex items-start gap-3">
                    <flux:icon.shield-check variant="outline"
                        class="flex-shrink-0 w-5 h-5 mt-0.5 text-blue-600 dark:text-blue-400" />
                    <div>
                        <flux:text class="font-medium text-blue-900 dark:text-blue-100">Secure Payment</flux:text>
                        <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                            Your payment is processed through CCAvenue's secure payment gateway. Your financial
                            information is encrypted and protected.
                        </flux:text>
                    </div>
                </div>
            </div>

            {{-- Hidden Form for Auto-Submission --}}
            <form method="post" name="redirect" action="{{ $gatewayUrl }}" class="hidden">
                <input type="hidden" name="encRequest" value="{{ $encRequest }}">
                <input type="hidden" name="access_code" value="{{ $accessCode }}">
            </form>
        </flux:card>

        {{-- Footer Note --}}
        <flux:text class="block mt-4 text-sm text-center text-zinc-500">
            If you are not redirected automatically within 6 seconds,
            <button onclick="document.redirect.submit()"
                class="font-medium underline text-primary-600 dark:text-primary-400 hover:text-primary-700">
                click here
            </button>
        </flux:text>
    </div>

    @fluxScripts

    <script>
        setTimeout(function() {
            document.redirect.submit();
        }, 6000);
    </script>
</body>

</html>
