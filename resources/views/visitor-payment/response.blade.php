<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head', ['title' => $isSuccess ? 'Payment Successful' : 'Payment Failed'])
</head>

<body class="flex items-center justify-center min-h-screen antialiased bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-2xl px-4 mx-auto">
        <flux:card class="p-8">
            {{-- Header Logos --}}
            <div class="flex items-center justify-between mb-8">
                <img src="{{ asset('brand/sgcci-logo-fixed.svg') }}" alt="SGCCI Logo" class="h-auto max-w-12">
                @if ($visitor?->exhibition?->logo_url)
                    <img src="{{ $visitor->exhibition->logo_url }}" alt="{{ $visitor->exhibition->title }} Logo" class="h-auto max-w-24">
                @endif
            </div>

            {{-- Status Icon & Title --}}
            <div class="mb-6 text-center">
                @if ($isSuccess)
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 mb-4 bg-green-100 rounded-full dark:bg-green-950/30">
                        <flux:icon.check variant="outline" class="w-12 h-12 text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="xl" class="mb-2 text-green-600 dark:text-green-400">
                        Payment Successful!
                    </flux:heading>
                    <flux:subheading class="text-zinc-600 dark:text-zinc-400">
                        Your visitor registration has been confirmed.
                    </flux:subheading>
                @else
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 mb-4 bg-red-100 rounded-full dark:bg-red-950/30">
                        <flux:icon.x-mark variant="outline" class="w-12 h-12 text-red-600 dark:text-red-400" />
                    </div>
                    <flux:heading size="xl" class="mb-2 text-red-600 dark:text-red-400">
                        Payment Failed
                    </flux:heading>
                    <flux:subheading class="text-zinc-600 dark:text-zinc-400">
                        We couldn't process your payment. Please try again.
                    </flux:subheading>
                @endif
            </div>

            <flux:separator class="my-6" />

            {{-- Transaction Details --}}
            <div class="space-y-4">
                <flux:heading size="lg" class="mb-4">Transaction Details</flux:heading>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Registration Code</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">
                        {{ $responseData['order_id'] ?? 'N/A' }}
                    </flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Tracking ID</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">
                        {{ $responseData['tracking_id'] ?? 'N/A' }}
                    </flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Payment Mode</flux:text>
                    <flux:text class="font-semibold text-black dark:text-white">
                        {{ $responseData['payment_mode'] ?? 'N/A' }}
                    </flux:text>
                </div>

                <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Status</flux:text>
                    <flux:text
                        class="font-semibold {{ $isSuccess ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $responseData['order_status'] ?? 'N/A' }}
                    </flux:text>
                </div>

                @if (isset($responseData['failure_message']) && ! $isSuccess)
                    <div class="p-4 mt-4 rounded-lg bg-red-50 dark:bg-red-950/30">
                        <flux:text class="text-sm text-red-700 dark:text-red-300">
                            <strong>Failure Reason:</strong> {{ $responseData['failure_message'] }}
                        </flux:text>
                    </div>
                @endif

                <div class="flex items-center justify-between py-4 pt-6">
                    <flux:heading size="lg">Amount {{ $isSuccess ? 'Paid' : 'Attempted' }}</flux:heading>
                    <flux:heading size="xl"
                        class="{{ $isSuccess ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ Number::currency((float) ($responseData['amount'] ?? 0), 'INR') }}
                    </flux:heading>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-8 space-y-3">
                @if ($isSuccess)
                    <flux:button variant="primary"
                        href="{{ route('visitors-registration.thank-you', ['exhibition' => $visitor->exhibition, 'registrationCode' => $visitor->registration_code]) }}"
                        class="w-full">
                        View Registration Details
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('home') }}" class="w-full">
                        Return to Home
                    </flux:button>
                @else
                    <flux:button variant="primary"
                        href="{{ route('visitor-payment.initiate', $visitor->registration_code) }}" class="w-full">
                        Try Again
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('home') }}" class="w-full">
                        Return to Home
                    </flux:button>
                @endif
            </div>

            {{-- Support Information --}}
            <div class="p-4 mt-6 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                <div class="flex items-start gap-3">
                    <flux:icon.information-circle variant="outline"
                        class="flex-shrink-0 w-5 h-5 mt-0.5 text-zinc-600 dark:text-zinc-400" />
                    <div>
                        <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">
                            If you have any questions about this transaction, please contact our support team with your
                            Registration Code: <strong>{{ $responseData['order_id'] ?? 'N/A' }}</strong>
                        </flux:text>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    @fluxScripts
</body>

</html>
