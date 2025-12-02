<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-mark class="block h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                @php($navRole = Auth::user()?->role)
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if ($navRole === 'assistant')
                        <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.index')">
                            {{ __('Attendance') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.index')">
                            {{ __('Absence Notes') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.index')">
                            {{ __('Weekly Exams') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.index')">
                            {{ __('Reports') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.index')">
                            {{ __('Teachers') }}
                        </x-nav-link>
                    @else
                        <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('students.index') }}" :active="request()->routeIs('students.index')">
                            {{ __('Students') }}
                        </x-nav-link>
                        @if (in_array($navRole, ['admin', 'director', 'lead_instructor', 'instructor']))
                            <x-nav-link href="{{ route('students.transfer') }}" :active="request()->routeIs('students.transfer')">
                                {{ __('Transfer') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.index')">
                            {{ __('Attendance') }}
                        </x-nav-link>
                        @if (in_array($navRole, ['admin', 'director', 'instructor']))
                            <x-nav-link href="{{ route('management.entries') }}" :active="request()->routeIs('management.entries')">
                                {{ __('Management Log') }}
                            </x-nav-link>
                        @endif
                        @if (in_array($navRole, ['admin', 'director']))
                            <x-nav-link href="{{ route('attendance.overview') }}" :active="request()->routeIs('attendance.overview')">
                                {{ __('Attendance Log') }}
                            </x-nav-link>
                            <x-nav-link href="{{ route('holidays.index') }}" :active="request()->routeIs('holidays.index')">
                                {{ __('Holidays') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link href="{{ route('fees.index') }}" :active="request()->routeIs('fees.index')">
                            {{ __('Fees') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.index')">
                            {{ __('Weekly Exams') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('due-list.index') }}" :active="request()->routeIs('due-list.index')">
                            {{ __('Due List') }}
                        </x-nav-link>
                        @if (in_array($navRole, ['instructor', 'assistant']))
                            <x-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.index')">
                                {{ __('Absence Notes') }}
                            </x-nav-link>
                        @endif
                        @if ($navRole === 'admin')
                            <x-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.index')">
                                {{ __('Team Members') }}
                            </x-nav-link>
                            <x-nav-link href="{{ route('ledger.index') }}" :active="request()->routeIs('ledger.index')">
                                {{ __('Ledger') }}
                            </x-nav-link>
                        @endif
                        @if (in_array($navRole, ['admin', 'director', 'instructor', 'assistant']))
                            <x-nav-link href="{{ route('leaderboard.index') }}" :active="request()->routeIs('leaderboard.index')">
                                {{ __('Leaderboard') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.index')">
                            {{ __('Teachers') }}
                        </x-nav-link>
                        <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.index')">
                            {{ __('Reports') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Teams Dropdown -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <div class="ms-3 relative">
                        <x-dropdown align="right" width="60">
                            <x-slot name="trigger">
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                        {{ Auth::user()->currentTeam->name }}

                                        <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                        </svg>
                                    </button>
                                </span>
                            </x-slot>

                            <x-slot name="content">
                                <div class="w-60">
                                    <!-- Team Management -->
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        {{ __('Manage Team') }}
                                    </div>

                                    <!-- Team Settings -->
                                    <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                        {{ __('Team Settings') }}
                                    </x-dropdown-link>

                                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                        <x-dropdown-link href="{{ route('teams.create') }}">
                                            {{ __('Create New Team') }}
                                        </x-dropdown-link>
                                    @endcan

                                    <!-- Team Switcher -->
                                    @if (Auth::user()->allTeams()->count() > 1)
                                        <div class="border-t border-gray-200"></div>

                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Switch Teams') }}
                                        </div>

                                        @foreach (Auth::user()->allTeams() as $team)
                                            <x-switchable-team :team="$team" />
                                        @endforeach
                                    @endif
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endif

                <!-- Settings Dropdown -->
                <div class="ms-3 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                    <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                </button>
                            @else
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                        {{ Auth::user()->name }}

                                        <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </span>
                            @endif
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Account') }}
                            </div>

                            <x-dropdown-link href="{{ route('profile.show') }}">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                    {{ __('API Tokens') }}
                                </x-dropdown-link>
                            @endif

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf

                                <x-dropdown-link href="{{ route('logout') }}"
                                         @click.prevent="$root.submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="size-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
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
            @if ($navRole === 'assistant')
                <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.index')">
                    {{ __('Attendance') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.index')">
                    {{ __('Absence Notes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.index')">
                    {{ __('Weekly Exams') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.index')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.index')">
                    {{ __('Teachers') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('students.index') }}" :active="request()->routeIs('students.index')">
                    {{ __('Students') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.index')">
                    {{ __('Attendance') }}
                </x-responsive-nav-link>
                @if (in_array($navRole, ['admin', 'director', 'instructor']))
                    <x-responsive-nav-link href="{{ route('management.entries') }}" :active="request()->routeIs('management.entries')">
                        {{ __('Management Log') }}
                    </x-responsive-nav-link>
                @endif
                @if (in_array($navRole, ['admin', 'director']))
                    <x-responsive-nav-link href="{{ route('attendance.overview') }}" :active="request()->routeIs('attendance.overview')">
                        {{ __('Attendance Log') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('holidays.index') }}" :active="request()->routeIs('holidays.index')">
                        {{ __('Holidays') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('students.transfer') }}" :active="request()->routeIs('students.transfer')">
                        {{ __('Transfer') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link href="{{ route('fees.index') }}" :active="request()->routeIs('fees.index')">
                    {{ __('Fees') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.index')">
                    {{ __('Weekly Exams') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('due-list.index') }}" :active="request()->routeIs('due-list.index')">
                    {{ __('Due List') }}
                </x-responsive-nav-link>
                @if (in_array($navRole, ['instructor', 'assistant']))
                    <x-responsive-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.index')">
                        {{ __('Absence Notes') }}
                    </x-responsive-nav-link>
                @endif
                @if ($navRole === 'admin')
                    <x-responsive-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.index')">
                        {{ __('Team Members') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('ledger.index') }}" :active="request()->routeIs('ledger.index')">
                        {{ __('Ledger') }}
                    </x-responsive-nav-link>
                @endif
                @if (in_array($navRole, ['admin', 'director', 'instructor', 'assistant']))
                    <x-responsive-nav-link href="{{ route('leaderboard.index') }}" :active="request()->routeIs('leaderboard.index')">
                        {{ __('Leaderboard') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.index')">
                    {{ __('Teachers') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.index')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <div class="shrink-0 me-3">
                        <img class="size-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                    </div>
                @endif

                <div>
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <x-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                        {{ __('API Tokens') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <x-responsive-nav-link href="{{ route('logout') }}"
                                   @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>

                <!-- Team Management -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <div class="border-t border-gray-200"></div>

                    <div class="block px-4 py-2 text-xs text-gray-400">
                        {{ __('Manage Team') }}
                    </div>

                    <!-- Team Settings -->
                    <x-responsive-nav-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}" :active="request()->routeIs('teams.show')">
                        {{ __('Team Settings') }}
                    </x-responsive-nav-link>

                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                        <x-responsive-nav-link href="{{ route('teams.create') }}" :active="request()->routeIs('teams.create')">
                            {{ __('Create New Team') }}
                        </x-responsive-nav-link>
                    @endcan

                    <!-- Team Switcher -->
                    @if (Auth::user()->allTeams()->count() > 1)
                        <div class="border-t border-gray-200"></div>

                        <div class="block px-4 py-2 text-xs text-gray-400">
                            {{ __('Switch Teams') }}
                        </div>

                        @foreach (Auth::user()->allTeams() as $team)
                            <x-switchable-team :team="$team" component="responsive-nav-link" />
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</nav>
