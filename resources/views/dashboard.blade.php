@php
    $formatter = fn ($value) => '৳ ' . number_format($value, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($user->isAdmin())
                {{-- Financial Snapshot --}}
                <div class="grid md:grid-cols-4 gap-4">
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Income (This Month)</p>
                        <p class="text-2xl font-bold text-green-600">{{ $formatter($financialSnapshot['income']) }}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Expenses (This Month)</p>
                        <p class="text-2xl font-bold text-red-600">{{ $formatter($financialSnapshot['expenses']) }}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Net Income</p>
                        <p class="text-2xl font-bold">{{ $formatter($financialSnapshot['net']) }}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow {{ $financialSnapshot['thresholdExceeded'] ? 'border border-red-400' : '' }}">
                        <p class="text-sm text-gray-500">Outstanding Dues</p>
                        <p class="text-2xl font-bold text-amber-600">{{ $formatter($financialSnapshot['outstanding']) }}</p>
                        @if ($financialSnapshot['thresholdExceeded'])
                            <p class="text-xs text-red-600 mt-2">Alert: outstanding dues exceeded the configured threshold.</p>
                        @endif
                    </div>
                </div>

                {{-- Enrollment & Status --}}
                <div class="bg-white rounded-lg shadow p-4 space-y-4">
                    <div class="grid md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Total Students</p>
                            <p class="text-2xl font-bold">{{ $studentCounts['total'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Active</p>
                            <p class="text-2xl font-bold text-green-600">{{ $studentCounts['active'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Inactive</p>
                            <p class="text-2xl font-bold text-gray-600">{{ $studentCounts['inactive'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Passed</p>
                            <p class="text-2xl font-bold text-indigo-600">{{ $studentCounts['passed'] }}</p>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-{{ max(1, $classDistribution->count()) }} gap-4">
                        @foreach ($classDistribution as $class => $count)
                            <div class="p-3 bg-gray-50 rounded border">
                                <p class="text-xs text-gray-500">{{ \App\Support\AcademyOptions::classLabel($class) }}</p>
                                <p class="text-lg font-semibold">{{ $count }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold text-gray-800">Attendance Today</h3>
                        <div class="space-y-2">
                            @forelse ($attendanceToday as $class => $statuses)
                                <div class="flex justify-between text-sm border-b pb-1">
                                    <span>{{ \App\Support\AcademyOptions::classLabel($class) }}</span>
                                    <span>
                                        P: {{ $statuses->firstWhere('status', 'present')->total ?? 0 }},
                                        A: {{ $statuses->firstWhere('status', 'absent')->total ?? 0 }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No attendance data for today.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold text-gray-800">Exam Performance (Last 7 days)</h3>
                        <div class="space-y-2">
                            @forelse ($examHealth as $item)
                                <div class="flex justify-between text-sm border-b pb-1">
                                    <span>{{ \App\Support\AcademyOptions::classLabel($item->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($item->section) }}</span>
                                    <span>{{ number_format($item->average, 1) }}%</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No marks recorded in the last week.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-4 space-y-3">
                        <h3 class="font-semibold text-gray-800">Recent Activity</h3>
                        <div>
                            <p class="text-xs text-gray-500">New Students</p>
                            <ul class="text-sm space-y-1">
                                @foreach ($recentActivities['students'] as $item)
                                    <li>{{ $item->name }} <span class="text-xs text-gray-500">— {{ optional($item->created_at)->diffForHumans() }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Payments</p>
                            <ul class="text-sm space-y-1">
                                @foreach ($recentActivities['payments'] as $payment)
                                    <li>{{ $payment->student->name ?? 'Student' }} paid {{ $formatter($payment->amount) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 space-y-3">
                        <div>
                            <p class="text-xs text-gray-500">Absence Notes</p>
                            <ul class="text-sm space-y-1">
                                @foreach ($recentActivities['notes'] as $note)
                                    <li>{{ $note->student->name ?? 'Student' }} — {{ $note->category }} ({{ optional($note->note_date)->format('d M') }})</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Expenses</p>
                            <ul class="text-sm space-y-1">
                                @foreach ($recentActivities['expenses'] as $expense)
                                    <li>{{ $expense->category }} — {{ $formatter($expense->amount) }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">New Instructors</p>
                            <ul class="text-sm space-y-1">
                                @foreach ($recentActivities['instructors'] as $instructor)
                                    <li>{{ $instructor->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Inactive / Absent Students --}}
                <livewire:dashboard.inactive-student-alert />

                {{-- Quick Actions --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <x-secondary-button onclick="window.location='{{ route('students.index') }}'">Add Student</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('ledger.index') }}'">Record Expense</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('users.index') }}'">Create Team Member</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('due-list.index') }}'">Download Due List</x-secondary-button>
                    </div>
                </div>

                {{-- Notifications --}}
                <div class="bg-white rounded-lg shadow p-4 space-y-3">
                    <h3 class="font-semibold text-gray-800">Notifications</h3>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Students with dues older than 2 months</p>
                        <ul class="text-sm space-y-1">
                            @forelse ($notifications['overdueStudents'] as $student)
                                <li>{{ $student->name }} – {{ $formatter($student->feeInvoices->sum(fn($invoice) => max(0, $invoice->amount_due - $invoice->amount_paid))) }}</li>
                            @empty
                                <li class="text-gray-500 text-sm">No overdue students 🎉</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Instructors pending weekly exam updates</p>
                        <ul class="text-sm space-y-1">
                            @forelse ($notifications['pendingInstructors'] as $name)
                                <li>{{ $name }}</li>
                            @empty
                                <li class="text-gray-500 text-sm">All instructors submitted this week.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @elseif ($user->isAssistant())
                @php
                    $hscOneCount = $classDistribution['hsc_1'] ?? 0;
                    $hscTwoCount = $classDistribution['hsc_2'] ?? 0;
                @endphp
                <div class="grid md:grid-cols-4 gap-4">
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Total Students</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $studentCounts['total'] }}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Active</p>
                        <p class="text-2xl font-bold text-green-600">{{ $studentCounts['active'] }}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Inactive</p>
                        <p class="text-2xl font-bold text-gray-600">{{ $studentCounts['inactive'] }}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <p class="text-sm text-gray-500">Passed</p>
                        <p class="text-2xl font-bold text-indigo-600">{{ $studentCounts['passed'] }}</p>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-600">HSC 1st Year</p>
                        <p class="text-2xl font-bold text-blue-700 mt-1">{{ $hscOneCount }}</p>
                    </div>
                    <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-600">HSC 2nd Year</p>
                        <p class="text-2xl font-bold text-indigo-700 mt-1">{{ $hscTwoCount }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800">Exam Performance Snapshot</h3>
                    <div class="space-y-2 mt-3">
                        @forelse ($examHealth as $item)
                            <div class="flex justify-between text-sm border-b pb-1">
                                <span>{{ \App\Support\AcademyOptions::classLabel($item->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($item->section) }}</span>
                                <span>{{ number_format($item->average, 1) }}%</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No weekly exam data recorded recently.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800">Attendance Snapshot (Today)</h3>
                    <div class="space-y-2 mt-3">
                        @forelse ($attendanceToday as $class => $statuses)
                            <div class="flex justify-between text-sm border-b pb-1">
                                <span>{{ \App\Support\AcademyOptions::classLabel($class) }}</span>
                                <span>P: {{ $statuses->firstWhere('status', 'present')->total ?? 0 }}, A: {{ $statuses->firstWhere('status', 'absent')->total ?? 0 }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No attendance records for today yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <x-secondary-button onclick="window.location='{{ route('attendance.index') }}'">Record Attendance</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('holidays.index') }}'">Manage Holidays</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('weekly-exams.index') }}'">Weekly Exams</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('leaderboard.index') }}'">Leaderboard</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('reports.index') }}'">Reports</x-secondary-button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 space-y-3">
                    <h3 class="font-semibold text-gray-800">Notifications</h3>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Instructors pending weekly exam updates</p>
                        <ul class="text-sm space-y-1">
                            @forelse ($notifications['pendingInstructors'] as $name)
                                <li>{{ $name }}</li>
                            @empty
                                <li class="text-gray-500 text-sm">All instructors submitted this week.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @else
                {{-- Instructor view --}}
                <div class="grid md:grid-cols-3 gap-4">
                    @foreach ($classPerformance as $item)
                        <div class="p-4 bg-white rounded-lg shadow">
                            <p class="text-sm text-gray-500">{{ \App\Support\AcademyOptions::classLabel($item->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($item->section) }}</p>
                            <p class="text-2xl font-bold">{{ number_format($item->average, 1) }}%</p>
                            <p class="text-xs text-gray-500 mt-1">Average score this month</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold text-gray-800">Frequent Absentees (This Month)</h3>
                        <ul class="text-sm space-y-2">
                            @forelse ($frequentAbsentees as $item)
                                <li>{{ $item->student->name ?? 'Student' }} — {{ $item->total }} absences</li>
                            @empty
                                <li class="text-gray-500 text-sm">No students exceeded the absence limit.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold text-gray-800">Recent Weekly Exams</h3>
                        <ul class="text-sm space-y-2">
                            @forelse ($recentExamMarks as $mark)
                                <li>{{ optional($mark->exam_date)->format('d M') }} • {{ $mark->student->name ?? 'Student' }} • {{ number_format(($mark->marks_obtained / max(1, $mark->max_marks)) * 100, 1) }}%</li>
                            @empty
                                <li class="text-gray-500 text-sm">No marks recorded recently.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Student Alerts</h3>
                    <ul class="text-sm space-y-1">
                        @forelse ($instructorStudentAlerts as $student)
                            <li>{{ $student->name }} — outstanding dues detected</li>
                        @empty
                            <li class="text-gray-500 text-sm">No alerts at the moment.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <x-secondary-button onclick="window.location='{{ route('attendance.index') }}'">Record Attendance</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('weekly-exams.index') }}'">Add Weekly Marks</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('reports.index') }}'">Download Student Report</x-secondary-button>
                        <x-secondary-button onclick="window.location='{{ route('notes.index') }}'">Absence Notes</x-secondary-button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
