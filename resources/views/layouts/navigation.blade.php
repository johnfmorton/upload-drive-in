<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="/">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    @if(auth()->user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @elseif(auth()->user()->isClient())
                        <x-nav-link :href="route('client.upload-files')" :active="request()->routeIs('client.upload-files')">
                            {{ __('Upload Files') }}
                        </x-nav-link>
                        <x-nav-link :href="route('client.my-uploads')" :active="request()->routeIs('client.my-uploads')">
                            {{ __('My Uploads') }}
                        </x-nav-link>
                    @elseif(auth()->user()->isEmployee())
                        <x-nav-link :href="route('employee.dashboard', ['username' => auth()->user()->username])" :active="request()->routeIs('employee.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endif
                </div>
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                {{ __('messages.nav_dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.index')">
                                {{ __('messages.nav_client_users') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.cloud-storage.index')" :active="request()->routeIs('admin.cloud-storage.index')">
                                {{ __('messages.nav_cloud_storage') }}
                            </x-nav-link>
                        @else
                            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('my-uploads')">
                                {{ __('messages.nav_your_files') }}
                            </x-nav-link>
                            <x-nav-link :href="route('upload-files')" :active="request()->routeIs('upload-files')">
                                {{ __('messages.nav_upload_files') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(auth()->user()->isAdmin())
                            <x-dropdown-link :href="route('admin.profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        @elseif(auth()->user()->isClient())
                            <x-dropdown-link :href="route('client.profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        @elseif(auth()->user()->isEmployee())
                            <x-dropdown-link :href="route('employee.profile.edit', ['username' => auth()->user()->username])">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        @endif

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
        @auth
            <div class="pt-2 pb-3 space-y-1">
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        {{ __('messages.nav_dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.index')">
                        {{ __('messages.nav_client_users') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.cloud-storage.index')" :active="request()->routeIs('admin.cloud-storage.index')">
                        {{ __('messages.nav_cloud_storage') }}
                    </x-responsive-nav-link>
                @else
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('my-uploads')">
                        {{ __('messages.nav_dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('upload-files')" :active="request()->routeIs('upload-files')">
                        {{ __('messages.nav_upload_files') }}
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
                    @if(auth()->user()->isAdmin())
                        <x-responsive-nav-link :href="route('admin.profile.edit')">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>
                    @elseif(auth()->user()->isClient())
                        <x-responsive-nav-link :href="route('client.profile.edit')">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>
                    @elseif(auth()->user()->isEmployee())
                        <x-responsive-nav-link :href="route('employee.profile.edit', ['username' => auth()->user()->username])">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>
                    @endif

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
        @else
            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link :href="route('login')">
                    {{ __('messages.auth_log_in') }}
                </x-responsive-nav-link>
            </div>
        @endauth
    </div>
</nav>
