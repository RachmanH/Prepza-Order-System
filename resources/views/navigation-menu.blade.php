<nav x-data="{ open: false }" class="bg-white border-b border-slate-100 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Left: Logo + Nav Links --}}
            <div class="flex items-center gap-6">
                {{-- Logo --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                    <span class="text-base font-bold text-slate-900 tracking-tight">Prepza</span>
                </a>

                {{-- Desktop Nav Links --}}
                <div class="hidden sm:flex items-center gap-1">
                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    <x-nav-link href="{{ route('order.kiosk') }}" :active="request()->routeIs('order.kiosk')">
                        Order Kiosk
                    </x-nav-link>
                    <x-nav-link href="{{ route('queue.management') }}" :active="request()->routeIs('queue.management') || request()->routeIs('cashier.panel')">
                        Antrian
                    </x-nav-link>
                    <x-nav-link href="{{ route('queue.board') }}" :active="request()->routeIs('queue.board')">
                        Display
                    </x-nav-link>
                    @if (Auth::user()?->isSuperAdmin())
                        <x-nav-link href="{{ route('menu.management') }}" :active="request()->routeIs('menu.management')">
                            Menu
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- Right: User Dropdown --}}
            <div class="hidden sm:flex items-center gap-3">
                {{-- Teams Dropdown --}}
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition duration-150">
                                {{ Auth::user()->currentTeam->name }}
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="p-1 w-60">
                                <div class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">Manage Team</div>
                                <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">Team Settings</x-dropdown-link>
                                @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                    <x-dropdown-link href="{{ route('teams.create') }}">Create New Team</x-dropdown-link>
                                @endcan
                                @if (Auth::user()->allTeams()->count() > 1)
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <div class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">Switch Teams</div>
                                    @foreach (Auth::user()->allTeams() as $team)
                                        <x-switchable-team :team="$team" />
                                    @endforeach
                                @endif
                            </div>
                        </x-slot>
                    </x-dropdown>
                @endif

                {{-- User Dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                            <button class="flex items-center gap-2 rounded-xl p-1 hover:bg-slate-50 transition duration-150 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                <img class="h-8 w-8 rounded-lg object-cover ring-2 ring-slate-100" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                            </button>
                        @else
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition duration-150">
                                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-brand-100 text-xs font-bold text-brand-700">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        @endif
                    </x-slot>

                    <x-slot name="content">
                        <div class="p-1">
                            <div class="px-3 py-2 border-b border-slate-100 mb-1">
                                <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <x-dropdown-link href="{{ route('profile.show') }}">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                Profile
                            </x-dropdown-link>
                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                                    API Tokens
                                </x-dropdown-link>
                            @endif
                            <div class="my-1 border-t border-slate-100"></div>
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();" class="text-red-600 hover:bg-red-50 hover:text-red-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                                    Keluar
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger --}}
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition duration-150 focus:outline-none">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-100">
        <div class="px-4 py-3 space-y-1">
            <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('order.kiosk') }}" :active="request()->routeIs('order.kiosk')">Order Kiosk</x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('queue.management') }}" :active="request()->routeIs('queue.management') || request()->routeIs('cashier.panel')">Manajemen Antrian</x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('queue.board') }}" :active="request()->routeIs('queue.board')">Display Antrian</x-responsive-nav-link>
            @if (Auth::user()?->isSuperAdmin())
                <x-responsive-nav-link href="{{ route('menu.management') }}" :active="request()->routeIs('menu.management')">Manajemen Menu</x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-slate-100 px-4 py-4">
            <div class="flex items-center gap-3 mb-3">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <img class="h-9 w-9 rounded-xl object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                @else
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 text-sm font-bold text-brand-700">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                </div>
            </div>

            <div class="space-y-1">
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">Profile</x-responsive-nav-link>
                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">API Tokens</x-responsive-nav-link>
                @endif
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();" class="text-red-600 hover:text-red-700 hover:bg-red-50 hover:border-red-200">
                        Keluar
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
