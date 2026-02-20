<div class="space-y-6">
    @if (! $student)
        <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
            No student profile is linked with your account. Contact admin to link your login credentials.
        </div>
    @else
        @if ($dueAlertMessage)
            <div class="fixed inset-0 z-[1000] bg-black bg-opacity-50 flex items-center justify-center p-4">
                <div class="w-full rounded-xl shadow-2xl overflow-hidden border border-red-200 bg-white" style="max-width: 560px;">
                    <div class="px-6 py-5 text-white" style="background: linear-gradient(120deg, #dc2626 0%, #f59e0b 100%);">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-xs uppercase tracking-wider text-red-100">Payment Due Alert</p>
                                <h3 class="text-xl font-bold leading-tight">বকেয়া নোটিশ</h3>
                                <p class="text-xs text-red-100">Please read and take action.</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide" style="background-color:#ffffff;color:#b91c1c;">
                                DUE
                            </span>
                        </div>
                    </div>

                    <div class="px-6 py-5 space-y-4" style="background: linear-gradient(180deg, #fff7ed 0%, #ffffff 60%);">
                        <div class="rounded-2xl bg-white p-5" style="box-shadow:0 10px 26px rgba(220,38,38,0.16);">
                            <div class="rounded-2xl p-4" style="background:linear-gradient(135deg,#fef2f2 0%,#fff7ed 100%);">
                                <p class="text-sm text-gray-700 leading-relaxed">{!! nl2br(e($dueAlertMessage)) !!}</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end bg-white">
                        <x-primary-button type="button" class="justify-center" wire:click="closeDueAlert">
                            ঠিক আছে
                        </x-primary-button>
                    </div>
                </div>
            </div>
        @elseif ($pendingNotice)
            <div class="fixed inset-0 z-[1000] bg-black bg-opacity-50 flex items-center justify-center p-4">
                <div class="w-full rounded-xl shadow-2xl overflow-hidden border border-pink-200 bg-white" style="max-width: 560px; min-height: 560px;">
                    <div class="px-6 py-5 text-white" style="background: linear-gradient(120deg, #7c3aed 0%, #ec4899 45%, #f59e0b 100%);">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-xs uppercase tracking-wider text-pink-100">Student Notice Board</p>
                                <h3 class="text-xl font-bold leading-tight">{{ $pendingNotice->title }}</h3>
                                <p class="text-xs text-pink-100">Published on {{ optional($pendingNotice->notice_date)->format('d M Y') }}</p>
                            </div>
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide"
                                style="background-color:#ffffff;color:#be185d;box-shadow:0 2px 10px rgba(0,0,0,0.12);"
                            >
                                NEW
                            </span>
                        </div>
                    </div>

                    <div class="px-6 py-5 space-y-4" style="background: linear-gradient(180deg, #fff7ed 0%, #ffffff 55%);">
                        <div class="rounded-2xl bg-white p-5" style="box-shadow:0 10px 26px rgba(251,146,60,0.18);">
                            <div class="rounded-2xl p-4" style="background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);">
                                <div class="text-sm text-gray-700 leading-relaxed break-words">{!! $pendingNotice->body !!}</div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            This notice will appear again on next login until you click <span class="font-semibold text-pink-700">Acknowledge</span>.
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 bg-white">
                        <x-secondary-button type="button" class="justify-center" wire:click="closeNotice({{ $pendingNotice->id }})">
                            Close for now
                        </x-secondary-button>
                        <x-primary-button type="button" class="justify-center" wire:click="acknowledgeNotice({{ $pendingNotice->id }})">
                            Acknowledge Notice
                        </x-primary-button>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">{{ $student->name }}</h3>
                    <p class="text-sm text-gray-500">
                        {{ \App\Support\AcademyOptions::classLabel($student->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($student->section) }}
                    </p>
                </div>
                <p class="text-sm text-gray-500 text-right whitespace-nowrap">
                    Admission Date: {{ optional($student->enrollment_date)->format('d M Y') ?? 'N/A' }}
                </p>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm text-gray-500">Due Months</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ $dueMonthCount }}</div>
                <div class="text-xs text-gray-500 mt-2">
                    @if (count($dueMonths))
                        {{ implode(', ', $dueMonths) }}
                    @else
                        No due months
                    @endif
                </div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm text-gray-500">Monthly Fee</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format((float) $student->monthly_fee, 2) }}</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm text-gray-500">Total Due</div>
                <div class="text-2xl font-bold text-red-700 mt-1">{{ number_format($dueAmount, 2) }}</div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <h4 class="font-semibold text-gray-800">Class Routine (Today)</h4>
            <div class="text-xs text-gray-500">Date: {{ now()->format('d M Y') }}</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-3 py-2">Time Slot</th>
                            <th class="px-3 py-2">Subject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($todayRoutines as $routine)
                            <tr>
                                <td class="px-3 py-2">{{ $routine->time_slot }}</td>
                                <td class="px-3 py-2">{{ \App\Support\AcademyOptions::subjectLabel($routine->subject) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-3 py-4 text-center text-gray-500">No class routine for today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 border border-indigo-100 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h4 class="font-semibold text-gray-800">Weekly Exam Performance</h4>
                    <p class="text-xs text-gray-500">Your personal weekly exam progress summary.</p>
                </div>
                <a href="{{ route('student.results') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                    View Full Results
                </a>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-3">
                    <div class="text-xs uppercase tracking-wide text-indigo-700 font-semibold">Average Score</div>
                    <div class="mt-1 text-2xl font-bold text-indigo-800">{{ number_format((float) $weeklyAveragePercent, 2) }}%</div>
                    <div class="text-xs text-indigo-700">{{ $weeklyPerformanceLabel }}</div>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-3">
                    <div class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Exams Recorded</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-800">{{ $weeklyExamCount }}</div>
                    @if (! is_null($weeklyTrendDelta))
                        <div class="text-xs {{ $weeklyTrendDelta >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $weeklyTrendDelta >= 0 ? '+' : '' }}{{ number_format($weeklyTrendDelta, 2) }}% vs previous 3 exams
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-2">
            <h4 class="font-semibold text-gray-800">Lecture Notes Alert</h4>
            @if ($noteCount > 0)
                <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                    {{ $latestNoteTeacherName ?? 'A teacher' }} has shared notes of {{ $latestNoteTitle ?? 'a lecture note' }}.
                </div>
            @else
                <div class="rounded-md border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">
                    No teacher note shared yet for your class and section.
                </div>
            @endif
        </div>
    @endif
</div>
