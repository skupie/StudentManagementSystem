<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-800">Model Test Results</h3>
                <p class="text-sm text-gray-500">Search, filter, and export results. Year defaults to {{ now()->year }}.</p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 md:items-end w-full md:w-auto">
                <div class="w-full md:w-52">
                    <x-input-label value="Student" />
                    <select wire:model.live="studentFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Select a student</option>
                        @foreach ($students as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <x-input-label value="Search" />
                    <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Student or test" />
                </div>
                <div class="w-full md:w-52">
                    <x-input-label value="Exam" />
                    <select wire:model.live="examFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All</option>
                        @foreach ($examOptions as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-52">
                    <x-input-label value="Subject" />
                    <select wire:model.live="subjectFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All</option>
                        @foreach ($subjectOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-40">
                    <x-input-label value="Section" />
                    <select wire:model.live="sectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All</option>
                        @foreach ($sectionOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 md:self-end">
                    <x-secondary-button type="button" wire:click="exportXlsx">
                        Export Report Card
                    </x-secondary-button>
                    <button type="button"
                        wire:click="publishPublic(true)"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-white text-sm font-semibold shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                        Publish
                    </button>
                    <button type="button"
                        wire:click="unpublishPublic"
                        class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-white text-sm font-semibold shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                        Unpublish
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4">
        @if (! $selectedStudent)
            <div class="text-center text-gray-500 py-6">Select a student to view results.</div>
        @else
            <div class="mb-4">
                <div class="text-sm text-gray-500">Showing results for:</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $selectedStudent->name }}
                    <span class="text-sm text-gray-500">({{ $selectedStudent->section }})</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2 text-left">Model Test</th>
                            <th class="px-3 py-2 text-left">Subject</th>
                            <th class="px-3 py-2 text-left">Marks</th>
                            <th class="px-3 py-2 text-left">Grade</th>
                            <th class="px-3 py-2 text-left">Grade Point</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($results as $row)
                            @php
                                $test = $row->test;
                                $subjectKey = $row->subject ?? $test?->subject;
                                $subjectLabel = $subjectOptions[$subjectKey] ?? ($subjectKey ?? '—');
                                $markParts = [];
                                if ($test?->type !== 'cq') {
                                    $markParts[] = 'MCQ: ' . ($row->mcq_mark !== null ? number_format($row->mcq_mark, 2) : '—');
                                }
                                if ($test?->type !== 'mcq') {
                                    $markParts[] = 'CQ: ' . ($row->cq_mark !== null ? number_format($row->cq_mark, 2) : '—');
                                }
                                if ($test?->type === 'full' && $row->practical_mark !== null) {
                                    $markParts[] = 'Practical: ' . number_format($row->practical_mark, 2);
                                }
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-gray-800">{{ $test?->name }}</div>
                                </td>
                                <td class="px-3 py-2">{{ $subjectLabel }}</td>
                                <td class="px-3 py-2 text-xs text-gray-700 space-y-1">
                                    @foreach ($markParts as $part)
                                        <div>{{ $part }}</div>
                                    @endforeach
                                    <div class="font-semibold text-gray-900">Total: {{ $row->total_mark !== null ? number_format($row->total_mark, 2) : '—' }}</div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $row->grade === 'F' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                        {{ $row->grade ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ $row->grade_point !== null ? number_format($row->grade_point, 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">No results found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $results->links() }}
            </div>

            <div class="mt-4 bg-gray-50 rounded-lg p-3 flex items-center justify-between">
                <div class="text-sm text-gray-600">Final grade (all subjects)</div>
                <div class="flex items-center gap-3">
                    <span class="text-lg font-semibold {{ $finalGrade === 'F' ? 'text-red-700' : 'text-gray-900' }}">{{ $finalGrade ?? '—' }}</span>
                    <span class="text-sm text-gray-600">GPA: {{ $finalGradePoint !== null ? number_format($finalGradePoint, 2) : '—' }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
