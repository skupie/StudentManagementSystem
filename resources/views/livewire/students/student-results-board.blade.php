<div class="space-y-6">
    @if (! $student)
        <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
            No student profile is linked with your account. Contact admin to link your login credentials.
        </div>
    @else
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800">Exam Results</h3>
            <p class="text-sm text-gray-500">{{ $student->name }}</p>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h4 class="font-semibold text-gray-800">Weekly Test Result</h4>
                <div class="w-full max-w-xs">
                    <x-input-label value="Select Week" />
                    <select wire:model.live="weeklyWeek" class="mt-1 block w-full rounded-md border-gray-300">
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
                            <th class="px-3 py-2">Subject</th>
                            <th class="px-3 py-2">Marks</th>
                            <th class="px-3 py-2">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($weeklyResults as $row)
                            <tr>
                                <td class="px-3 py-2">{{ optional($row->exam_date)->format('d M Y') }}</td>
                                <td class="px-3 py-2">{{ \App\Support\AcademyOptions::subjectLabel($row->subject) }}</td>
                                <td class="px-3 py-2">{{ $row->marks_obtained }} / {{ $row->max_marks }}</td>
                                <td class="px-3 py-2">{{ $row->remarks ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">No weekly test result found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h4 class="font-semibold text-gray-800">Model Test Result</h4>
                <div class="w-full max-w-xs">
                    <x-input-label value="Model Test Name" />
                    <select wire:model.live="modelExam" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All Model Tests</option>
                        @foreach ($modelExamOptions as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-3 py-2">Exam</th>
                            <th class="px-3 py-2">Subject</th>
                            <th class="px-3 py-2">Year</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($modelResults as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row->test?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ \App\Support\AcademyOptions::subjectLabel($row->subject ?? '') }}</td>
                                <td class="px-3 py-2">{{ $row->year }}</td>
                                <td class="px-3 py-2">{{ number_format((float) ($row->total_mark ?? 0), 2) }}</td>
                                <td class="px-3 py-2">{{ $row->grade ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">No model test result found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
