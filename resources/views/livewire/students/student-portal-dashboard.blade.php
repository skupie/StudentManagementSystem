<div class="space-y-6">
    @if (! $student)
        <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
            No student profile is linked with your account. Contact admin to link your login credentials.
        </div>
    @else
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800">{{ $student->name }}</h3>
            <p class="text-sm text-gray-500">
                {{ \App\Support\AcademyOptions::classLabel($student->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($student->section) }}
            </p>
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
