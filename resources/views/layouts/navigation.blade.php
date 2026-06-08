@php
    $approvalBadge = $queueBadges['approval'] ?? ['total' => 0, 'urgent' => 0, 'overdue' => 0];
    $kasubbagBadge = $queueBadges['kasubbag'] ?? ['total' => 0, 'urgent' => 0, 'overdue' => 0];

    $approvalBadgeClasses = $approvalBadge['overdue'] > 0
        ? 'bg-red-100 text-red-700'
        : ($approvalBadge['urgent'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700');

    $kasubbagBadgeClasses = $kasubbagBadge['overdue'] > 0
        ? 'bg-red-100 text-red-700'
        : ($kasubbagBadge['urgent'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-cyan-100 text-cyan-800');

    $approvalBadgeTitle = 'total: '.$approvalBadge['total'].', urgent: '.$approvalBadge['urgent'].', overdue: '.$approvalBadge['overdue'];
    $kasubbagBadgeTitle = 'total: '.$kasubbagBadge['total'].', urgent: '.$kasubbagBadge['urgent'].', overdue: '.$kasubbagBadge['overdue'];
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.index') || request()->routeIs('bookings.create') || request()->routeIs('bookings.show') || request()->routeIs('bookings.edit') || request()->routeIs('bookings.update')">
                        {{ __('Pengajuan') }}
                    </x-nav-link>
                    <x-nav-link :href="route('rooms.availability')" :active="request()->routeIs('rooms.availability')">
                        {{ __('Ketersediaan Ruangan') }}
                    </x-nav-link>
                    @can('manage-master-data')
                        <x-nav-link :href="route('master.rooms.index')" :active="request()->routeIs('master.*')">
                            {{ __('Master Data') }}
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()->hasRole('admin') || Auth::user()->hasAnyRole(['ketua_ormawa', 'ketua_ukm', 'kaprodi', 'pembina_ukm', 'wadir3', 'kasubbag']))
                        <x-nav-link :href="route('bookings.approval-queue')" :active="request()->routeIs('bookings.approval-queue')">
                            <span>{{ __('Approval Queue') }}</span>
                            @if (($approvalBadge['total'] ?? 0) > 0)
                                <span title="{{ $approvalBadgeTitle }}" class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $approvalBadgeClasses }}">
                                    {{ $approvalBadge['total'] }}@if (($approvalBadge['overdue'] ?? 0) > 0)!@endif
                                </span>
                            @endif
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('kasubbag'))
                        <x-nav-link :href="route('bookings.kasubbag-queue')" :active="request()->routeIs('bookings.kasubbag-queue') || request()->routeIs('bookings.kasubbag-review')">
                            <span>{{ __('Kasubbag Queue') }}</span>
                            @if (($kasubbagBadge['total'] ?? 0) > 0)
                                <span title="{{ $kasubbagBadgeTitle }}" class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $kasubbagBadgeClasses }}">
                                    {{ $kasubbagBadge['total'] }}@if (($kasubbagBadge['overdue'] ?? 0) > 0)!@endif
                                </span>
                            @endif
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.index') || request()->routeIs('bookings.create') || request()->routeIs('bookings.show') || request()->routeIs('bookings.edit') || request()->routeIs('bookings.update')">
                {{ __('Pengajuan') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('rooms.availability')" :active="request()->routeIs('rooms.availability')">
                {{ __('Ketersediaan Ruangan') }}
            </x-responsive-nav-link>
            @can('manage-master-data')
                <x-responsive-nav-link :href="route('master.rooms.index')" :active="request()->routeIs('master.*')">
                    {{ __('Master Data') }}
                </x-responsive-nav-link>
            @endcan
            @if (Auth::user()->hasRole('admin') || Auth::user()->hasAnyRole(['ketua_ormawa', 'ketua_ukm', 'kaprodi', 'pembina_ukm', 'wadir3', 'kasubbag']))
                <x-responsive-nav-link :href="route('bookings.approval-queue')" :active="request()->routeIs('bookings.approval-queue')">
                    {{ __('Approval Queue') }}
                    @if (($approvalBadge['total'] ?? 0) > 0)
                        ({{ $approvalBadge['total'] }} | U:{{ $approvalBadge['urgent'] }} | O:{{ $approvalBadge['overdue'] }})
                    @endif
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('kasubbag'))
                <x-responsive-nav-link :href="route('bookings.kasubbag-queue')" :active="request()->routeIs('bookings.kasubbag-queue') || request()->routeIs('bookings.kasubbag-review')">
                    {{ __('Kasubbag Queue') }}
                    @if (($kasubbagBadge['total'] ?? 0) > 0)
                        ({{ $kasubbagBadge['total'] }} | U:{{ $kasubbagBadge['urgent'] }} | O:{{ $kasubbagBadge['overdue'] }})
                    @endif
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
