<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Left --}}
            <div class="flex items-center">
                {{-- Logo --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <x-application-mark class="block h-9 w-auto" />
                </a>

                @php($navRole = Auth::user()?->role)

                {{-- Desktop nav --}}
                <div class="hidden sm:flex sm:items-center sm:ms-8 gap-4">

                    {{-- Common --}}
                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    {{-- Assistant menu --}}
                    @if ($navRole === 'assistant')
                        <x-nav-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.*')">
                            {{ __('Attendance') }}
                        </x-nav-link>

                        <x-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.*')">
                            {{ __('Absence Notes') }}
                        </x-nav-link>

                        <x-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.*')">
                            {{ __('Weekly Exams') }}
                        </x-nav-link>

                        <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')">
                            {{ __('Reports') }}
                        </x-nav-link>

                        <x-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.*')">
                            {{ __('Teachers') }}
                        </x-nav-link>

                    @else
                        {{-- Students dropdown --}}
                        <x-nav-dropdown label="Students" :active="request()->routeIs('students.*') || request()->routeIs('attendance.*') || request()->routeIs('notes.*')">
                            <div class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                {{ __('Students') }}
                            </div>

                            <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                               href="{{ route('students.index') }}">
                                {{ __('Directory') }}
                            </a>

                            <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                               href="{{ route('attendance.index') }}">
                                {{ __('Attendance') }}
                            </a>

                            @if (in_array($navRole, ['instructor', 'assistant']))
                                <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                   href="{{ route('notes.index') }}">
                                    {{ __('Attendance Notes') }}
                                </a>
                            @endif

                            @if (in_array($navRole, ['admin', 'director', 'lead_instructor', 'instructor']))
                                <div class="my-2 border-t border-gray-100"></div>
                                <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                   href="{{ route('students.transfer') }}">
                                    {{ __('Transfer') }}
                                </a>
                            @endif

                            @if (in_array($navRole, ['admin', 'director', 'instructor', 'assistant']))
                                <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                   href="{{ route('leaderboard.index') }}">
                                    {{ __('Leaderboard') }}
                                </a>
                            @endif
                        </x-nav-dropdown>

                        {{-- Payment dropdown --}}
                        <x-nav-dropdown label="Payment" :active="request()->routeIs('fees.*') || request()->routeIs('due-list.*')">
                            <div class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                {{ __('Payment') }}
                            </div>

                            <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                               href="{{ route('fees.index') }}">
                                {{ __('Fees') }}
                            </a>
                            <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                               href="{{ route('due-list.index') }}">
                                {{ __('Due List') }}
                            </a>
                        </x-nav-dropdown>

                        {{-- Logs dropdown --}}
                        @if (in_array($navRole, ['admin', 'director', 'instructor']))
                            <x-nav-dropdown label="Logs" :active="request()->routeIs('management.entries') || request()->routeIs('attendance.overview')">
                                <div class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                    {{ __('Logs') }}
                                </div>

                                <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                   href="{{ route('management.entries') }}">
                                    {{ __('Management Log') }}
                                </a>

                                @if (in_array($navRole, ['admin', 'director']))
                                    <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                       href="{{ route('attendance.overview') }}">
                                        {{ __('Attendance Log') }}
                                    </a>
                                @endif
                            </x-nav-dropdown>
                        @endif

                        {{-- Holidays --}}
                        @if (in_array($navRole, ['admin', 'director']))
                            <x-nav-link href="{{ route('holidays.index') }}" :active="request()->routeIs('holidays.*')">
                                {{ __('Holidays') }}
                            </x-nav-link>
                        @endif

                        {{-- Weekly Exams --}}
                        <x-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.*')">
                            {{ __('Weekly Exams') }}
                        </x-nav-link>

                        {{-- Admin-only --}}
                        @if ($navRole === 'admin')
                            <x-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')">
                                {{ __('Team Members') }}
                            </x-nav-link>
                            <x-nav-link href="{{ route('ledger.index') }}" :active="request()->routeIs('ledger.*')">
                                {{ __('Ledger') }}
                            </x-nav-link>
                        @endif

                        {{-- Classes & Sections --}}
                        @if (in_array($navRole, ['admin', 'director', 'instructor']))
                            <x-nav-link href="{{ route('class.sections') }}" :active="request()->routeIs('class.sections')">
                                {{ __('Classes & Sections') }}
                            </x-nav-link>
                        @endif

                        {{-- Teachers --}}
                        @if (in_array($navRole, ['admin', 'director']))
                            <x-nav-dropdown label="Teachers" :active="request()->routeIs('teachers.*') || request()->routeIs('teacher.payments')">
                                <div class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                    {{ __('Teachers') }}
                                </div>

                                <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                   href="{{ route('teachers.index') }}">
                                    {{ __('Directory') }}
                                </a>
                                <a class="flex items-center rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                   href="{{ route('teacher.payments') }}">
                                    {{ __('Teacher Payments') }}
                                </a>
                            </x-nav-dropdown>
                        @else
                            <x-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.*')">
                                {{ __('Teachers') }}
                            </x-nav-link>
                        @endif

                        {{-- Reports + Routines --}}
                        <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')">
                            {{ __('Reports') }}
                        </x-nav-link>

                        <x-nav-link href="{{ route('routines.index') }}" :active="request()->routeIs('routines.*')">
                            {{ __('Routines') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- Right --}}
            <div class="hidden sm:flex sm:items-center sm:gap-3">
                {{-- Teams --}}
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <div class="ms-3 relative">
                        <x-dropdown align="right" width="60">
                            <x-slot name="trigger">
                                <button type="button"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition">
                                    {{ Auth::user()->currentTeam->name }}
                                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="w-60">
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        {{ __('Manage Team') }}
                                    </div>
                                    <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                        {{ __('Team Settings') }}
                                    </x-dropdown-link>
                                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                        <x-dropdown-link href="{{ route('teams.create') }}">
                                            {{ __('Create New Team') }}
                                        </x-dropdown-link>
                                    @endcan

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

                {{-- Profile --}}
                <div class="ms-1 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition">
                                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                    <img class="h-9 w-9 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                @else
                                    <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                                @endif
                                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
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

                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            {{-- Mobile hamburger --}}
            {{-- Mobile hamburger --}}
                <div class="-me-2 flex items-center sm:hidden">
                    <button
                        @click="open = ! open"
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl p-2
                            text-sky-600 hover:text-sky-700 focus:outline-none
                            focus:ring-2 focus:ring-indigo-500/20 transition"
                        aria-label="Toggle navigation"
                        :aria-expanded="open.toString()"
                    >
                        <div class="flex flex-col justify-center gap-1.5" aria-hidden="true">
                            <span class="block h-1 w-7 rounded-full bg-sky-500"></span>
                            <span class="block h-1 w-7 rounded-full bg-sky-500"></span>
                            <span class="block h-1 w-7 rounded-full bg-sky-500"></span>
                        </div>
                        <span class="ml-2 text-sm font-semibold text-sky-600">Menu</span>
                    </button>
                </div>


        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-100">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <div class="pt-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ __('Students') }}</div>
            <x-responsive-nav-link href="{{ route('students.index') }}" :active="request()->routeIs('students.*')">
                {{ __('Directory') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('attendance.index') }}" :active="request()->routeIs('attendance.*')">
                {{ __('Attendance') }}
            </x-responsive-nav-link>
            @if (in_array($navRole, ['instructor', 'assistant']))
                <x-responsive-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.*')">
                    {{ __('Attendance Notes') }}
                </x-responsive-nav-link>
            @endif
            @if (in_array($navRole, ['admin', 'director', 'lead_instructor', 'instructor']))
                <x-responsive-nav-link href="{{ route('students.transfer') }}" :active="request()->routeIs('students.transfer')">
                    {{ __('Transfer') }}
                </x-responsive-nav-link>
            @endif
            @if (in_array($navRole, ['admin', 'director', 'instructor', 'assistant']))
                <x-responsive-nav-link href="{{ route('leaderboard.index') }}" :active="request()->routeIs('leaderboard.*')">
                    {{ __('Leaderboard') }}
                </x-responsive-nav-link>
            @endif

            <div class="pt-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ __('Payment') }}</div>
            <x-responsive-nav-link href="{{ route('fees.index') }}" :active="request()->routeIs('fees.*')">
                {{ __('Fees') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('due-list.index') }}" :active="request()->routeIs('due-list.*')">
                {{ __('Due List') }}
            </x-responsive-nav-link>

            @if (in_array($navRole, ['admin', 'director', 'instructor']))
                <div class="pt-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ __('Logs') }}</div>
                <x-responsive-nav-link href="{{ route('management.entries') }}" :active="request()->routeIs('management.entries')">
                    {{ __('Management Log') }}
                </x-responsive-nav-link>
                @if (in_array($navRole, ['admin', 'director']))
                    <x-responsive-nav-link href="{{ route('attendance.overview') }}" :active="request()->routeIs('attendance.overview')">
                        {{ __('Attendance Log') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if (in_array($navRole, ['admin', 'director']))
                <x-responsive-nav-link href="{{ route('holidays.index') }}" :active="request()->routeIs('holidays.*')">
                    {{ __('Holidays') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link href="{{ route('weekly-exams.index') }}" :active="request()->routeIs('weekly-exams.*')">
                {{ __('Weekly Exams') }}
            </x-responsive-nav-link>

            @if ($navRole === 'admin')
                <x-responsive-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')">
                    {{ __('Team Members') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('ledger.index') }}" :active="request()->routeIs('ledger.*')">
                    {{ __('Ledger') }}
                </x-responsive-nav-link>
            @endif

            @if (in_array($navRole, ['admin', 'director', 'instructor']))
                <x-responsive-nav-link href="{{ route('class.sections') }}" :active="request()->routeIs('class.sections')">
                    {{ __('Classes & Sections') }}
                </x-responsive-nav-link>
            @endif

            @if (in_array($navRole, ['admin', 'director']))
                <x-responsive-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.*')">
                    {{ __('Teachers') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('teacher.payments') }}" :active="request()->routeIs('teacher.payments')">
                    {{ __('Teacher Payments') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.*')">
                    {{ __('Teachers') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')">
                {{ __('Reports') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('routines.index') }}" :active="request()->routeIs('routines.*')">
                {{ __('Routines') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-3 border-t border-gray-100 px-4">
            <div class="flex items-center gap-3">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                @endif
                <div>
                    <div class="font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link href="{{ route('profile.show') }}">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
