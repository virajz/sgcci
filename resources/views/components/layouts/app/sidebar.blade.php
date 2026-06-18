<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 me-5 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
            </flux:navlist.group>

            @if(auth()->user()->isExhibitor())
                <flux:navlist.group :heading="__('Exhibitor')" class="grid">
                    <flux:navlist.item icon="identification" :href="route('exhibitor.badges.index')"
                        :current="request()->routeIs('exhibitor.badges.*')" wire:navigate>{{ __('Badges') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="building-storefront" :href="route('exhibitor.company-profile')"
                        :current="request()->routeIs('exhibitor.company-profile')" wire:navigate>{{ __('Company Profile') }}
                    </flux:navlist.item>
                    @if(auth()->user()->booking?->exhibition?->hasCustomInvitationPass())
                        <flux:navlist.item icon="ticket" :href="route('exhibitor.invitation-pass')"
                            :current="request()->routeIs('exhibitor.invitation-pass')" wire:navigate>{{ __('Invitation Pass') }}
                        </flux:navlist.item>
                    @endif
                    @if(auth()->user()->booking?->invited_guests_limit > 0)
                        <flux:navlist.item icon="user-plus" :href="route('exhibitor.invited-guests')"
                            :current="request()->routeIs('exhibitor.invited-guests')" wire:navigate>{{ __('Invited Guests') }}
                        </flux:navlist.item>
                    @endif
                    <flux:navlist.item icon="bookmark" :href="route('exhibitor.leads')"
                        :current="request()->routeIs('exhibitor.leads')" wire:navigate>{{ __('Leads') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif

            @if(auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Exhibition')" class="grid">
                    <flux:navlist.item icon="building-office" :href="route('admin.exhibitions.index')"
                        :current="request()->routeIs('admin.exhibitions.*')" wire:navigate>{{ __('Exhibitions') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-list" :href="route('admin.inquiries.index')"
                        :current="request()->routeIs('admin.inquiries.*')" wire:navigate>{{ __('Inquiries') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="identification" :href="route('admin.exhibitors.index')"
                        :current="request()->routeIs('admin.exhibitors.*')" wire:navigate>{{ __('Exhibitors') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Visitors')" class="grid">
                    <flux:navlist.item icon="user-group" :href="route('admin.visitors.index')"
                        :current="request()->routeIs('admin.visitors.*')" wire:navigate>{{ __('Visitors') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-plus" :href="route('admin.walk-in-visitors.index')"
                        :current="request()->routeIs('admin.walk-in-visitors.*')" wire:navigate>{{ __('Walk-in Visitors') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="shield-check" :href="route('admin.scans.index')"
                        :current="request()->routeIs('admin.scans.*')" wire:navigate>{{ __('Entry Scans') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Members')" class="grid">
                    <flux:navlist.item icon="user-group" :href="route('admin.committee-members.index')"
                        :current="request()->routeIs('admin.committee-members.*')" wire:navigate>{{ __('Managing Committee') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="identification" :href="route('admin.members.index')"
                        :current="request()->routeIs('admin.members.*')" wire:navigate>{{ __('SGCCI Members') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Masters')" class="grid">
                    <flux:navlist.item icon="tag" :href="route('admin.segments.index')"
                        :current="request()->routeIs('admin.segments.*')" wire:navigate>{{ __('Segments') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Team')" class="grid">
                    <flux:navlist.item icon="users" :href="route('admin.staff-members.index')"
                        :current="request()->routeIs('admin.staff-members.*')" wire:navigate>{{ __('Staff Members') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="ticket" :href="route('admin.support-tickets.index')"
                        :current="request()->routeIs('admin.support-tickets.*')" wire:navigate>{{ __('Support Tickets') }}
                    </flux:navlist.item>
                    @if(auth()->user()->email === 'viraj@sgcci.in')
                        <flux:navlist.item icon="circle-stack" :href="route('admin.database-backups')"
                            :current="request()->routeIs('admin.database-backups')" wire:navigate>{{ __('Database Backups') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="chat-bubble-left-ellipsis" :href="route('admin.whatsapp-webhook-logs')"
                            :current="request()->routeIs('admin.whatsapp-webhook-logs')" wire:navigate>{{ __('WhatsApp Logs') }}
                        </flux:navlist.item>
                    @endif
                </flux:navlist.group>

                @if(auth()->user()->email === 'viraj@sgcci.in')
                <flux:navlist.group :heading="__('Analytics')" class="grid">
                    <flux:navlist.item icon="chart-bar" :href="route('admin.analytics.exhibitor-leads')"
                        :current="request()->routeIs('admin.analytics.exhibitor-leads')" wire:navigate>{{ __('Exhibitor Leads') }}
                    </flux:navlist.item>
                </flux:navlist.group>
                @endif

            @endif

            @if(auth()->user()->isFrontDesk())
                <flux:navlist.group :heading="__('Front Desk')" class="grid">
                    <flux:navlist.item icon="computer-desktop" :href="route('front-desk.index')"
                        :current="request()->routeIs('front-desk.index')" wire:navigate>{{ __('Visitor Station') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif
        </flux:navlist>

        <flux:spacer />

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex w-8 h-8 overflow-hidden rounded-lg shrink-0">
                                <span
                                    class="flex items-center justify-center w-full h-full text-black rounded-lg bg-neutral-200 dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-sm leading-tight text-start">
                                <span class="font-semibold truncate">{{ auth()->user()->name }}</span>
                                <span class="text-xs truncate">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        @if(auth()->user()->isAdmin())
            <livewire:exhibition-selector />
        @endif

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex w-8 h-8 overflow-hidden rounded-lg shrink-0">
                                <span
                                    class="flex items-center justify-center w-full h-full text-black rounded-lg bg-neutral-200 dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-sm leading-tight text-start">
                                <span class="font-semibold truncate">{{ auth()->user()->name }}</span>
                                <span class="text-xs truncate">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}</flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    @if(auth()->user()->isAdmin())
        <flux:header class="hidden lg:flex bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Select Exhibition') }}
                </flux:text>
                <livewire:exhibition-selector />
            </div>
        </flux:header>

        <livewire:exhibition-splash />
    @endif

    {{ $slot }}

    @persist('toast')
        <flux:toast />
    @endpersist

    @fluxScripts
</body>

</html>
