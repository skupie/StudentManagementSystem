<div class="space-y-6">
    @if (! $student)
        <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
            No student profile is linked with your account. Contact admin to link your login credentials.
        </div>
    @else
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800">Routine</h3>
            <p class="text-sm text-gray-500">
                {{ \App\Support\AcademyOptions::classLabel($student->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($student->section) }}
            </p>
        </div>

        @if (($view ?? 'weekly') === 'weekly')
            <div id="weekly-test-routine" class="bg-white shadow rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="font-semibold text-gray-800">Weekly Test Routine</h4>
                    <div class="w-full max-w-xs">
                        <x-input-label value="Week" />
                        <select wire:model.live="weekStart" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="all">All Weeks</option>
                            @foreach ($weeklyWeekOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Exam</th>
                                <th class="px-3 py-2">Subject</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($weeklyAssignments as $item)
                                <tr>
                                    <td class="px-3 py-2">{{ optional($item->exam_date)->format('d M Y') }}</td>
                                    <td class="px-3 py-2">{{ $item->exam_name }}</td>
                                    <td class="px-3 py-2">{{ \App\Support\AcademyOptions::subjectLabel($item->subject) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">No weekly test routine found for the selected week.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="weekly-test-syllabus" class="bg-white shadow rounded-lg p-4 space-y-3">
                <h4 class="font-semibold text-gray-800">Weekly Test Syllabus</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <th class="px-3 py-2">Exam Date</th>
                                <th class="px-3 py-2">Subject</th>
                                <th class="px-3 py-2">Title</th>
                                <th class="px-3 py-2">Syllabus</th>
                                <th class="px-3 py-2">Shared By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($weeklySyllabi as $item)
                                <tr>
                                    <td class="px-3 py-2">{{ optional($item->week_start_date)->format('d M Y') }}</td>
                                    <td class="px-3 py-2">{{ \App\Support\AcademyOptions::subjectLabel($item->subject) }}</td>
                                    <td class="px-3 py-2">{{ $item->title }}</td>
                                    <td class="px-3 py-2">{{ $item->syllabus_details }}</td>
                                    <td class="px-3 py-2">{{ $item->creator?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">No syllabus found for the selected week.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div id="class-routine" class="bg-white shadow rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="font-semibold text-gray-800">Class Routine</h4>
                    <div class="w-full max-w-xs">
                        <x-input-label value="Date" />
                        <input type="date" wire:model.live="classDate" class="mt-1 block w-full rounded-md border-gray-300" list="class-routine-dates" />
                        <datalist id="class-routine-dates">
                            @foreach ($classDateOptions as $date)
                                <option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-4 space-y-3 border border-gray-100">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ \App\Support\AcademyOptions::classLabel($student->class_level) }} - {{ \App\Support\AcademyOptions::sectionLabel($student->section) }}</h3>
                        <p class="text-xs text-gray-500">Date: {{ \Carbon\Carbon::parse($classDate)->format('d M Y') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2 text-left">Time</th>
                                    <th class="px-3 py-2 text-left">Subject</th>
                                    <th class="px-3 py-2 text-left">Teacher</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($routines as $row)
                                    <tr>
                                        <td class="px-3 py-2">{{ $row->time_slot }}</td>
                                        <td class="px-3 py-2">{{ \App\Support\AcademyOptions::subjectLabel($row->subject) }}</td>
                                        <td class="px-3 py-2">{{ $row->teacher?->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-gray-500">No entries for this date.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
