<div class="space-y-8" wire:key="leaderboard-{{ $monthFilter }}">
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-[1px] rounded-2xl shadow-lg">
        <div class="bg-white rounded-2xl p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <p class="uppercase text-xs tracking-widest text-gray-500">League Table</p>
                    <h2 class="text-2xl font-bold text-gray-900">Class Leaderboard</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Recognizing the most diligent attendees and high-performing students across every class & section.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 items-center">
                    <div>
                        <x-input-label value="Filter Month" />
                        <x-text-input
                            type="month"
                            wire:model.live="monthFilter"
                            value="{{ $monthFilter }}"
                            wire:key="leaderboard-month-filter"
                            class="mt-1 block w-full sm:w-44"
                        />
                    </div>
                    <div class="text-sm text-gray-600">Showing {{ $monthLabel }}</div>
                    <div class="rounded-xl px-4 py-2 text-center min-w-[130px]" style="background-color:#d97706;">
                        <p class="text-xs uppercase tracking-wide text-white">Attendance Stars</p>
                        <p class="text-lg font-semibold text-white">
                            {{ $groups->filter(fn ($g) => $g['attendance']->isNotEmpty())->count() }}
                        </p>
                    </div>
                    <div class="rounded-xl px-4 py-2 text-center min-w-[130px]" style="background-color:#d97706;">
                        <p class="text-xs uppercase tracking-wide text-white">Exam Stars</p>
                        <p class="text-lg font-semibold text-white">
                            {{ $groups->filter(fn ($g) => $g['exam']->isNotEmpty())->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @forelse ($groups as $group)
            @php($attendanceStars = $group['attendance'])
            @php($examStars = $group['exam'])
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 space-y-5 hover:shadow-xl transition" wire:key="lb-{{ $monthFilter }}-{{ $group['class_label'] }}-{{ $group['section_label'] }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">Class / Section</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $group['class_label'] }} / {{ $group['section_label'] }}</h3>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-600">
                        Champions
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center rounded-full bg-green-100 text-green-600 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <p class="text-sm font-semibold text-gray-700">Top Attendance</p>
                    </div>
                    @if ($attendanceStars->isNotEmpty())
                        <div class="bg-gray-50 rounded-xl p-3 space-y-2">
                            @foreach ($attendanceStars as $star)
                                <div class="flex items-center justify-between text-sm text-gray-700">
                                    <span>{{ $star['name'] }}</span>
                                    <span class="text-gray-500">{{ $star['total'] }} days</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No attendance records yet.</p>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center rounded-full bg-amber-100 text-amber-600 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </span>
                        <p class="text-sm font-semibold text-gray-700">
                            Weekly Exam Stars
                            <span class="text-xs font-normal text-gray-500">(absent students excluded)</span>
                        </p>
                    </div>
                    @if ($examStars->isNotEmpty())
                        <div class="bg-indigo-50 rounded-xl p-3 space-y-2">
                            @foreach ($examStars as $star)
                                <div class="flex items-center justify-between text-sm text-indigo-900">
                                    <span>{{ $star['name'] }}</span>
                                    <span class="font-semibold">{{ $star['average'] }}% avg</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No eligible exam data yet.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-500 text-sm">No active class/section combinations found.</p>
            </div>
        @endforelse
    </div>
</div>
