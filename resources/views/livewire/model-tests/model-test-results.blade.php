<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-800">Model Test Results</h3>
                <p class="text-sm text-gray-500">Search, filter, and export results. Year defaults to {{ now()->year }}.</p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 md:items-end w-full md:w-auto">
                <div class="w-full md:w-48">
                    <x-input-label value="Search" />
                    <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Student or test" />
                </div>
                <div class="w-full md:w-28">
                    <x-input-label value="Year" />
                    <x-text-input type="number" wire:model.live="year" class="mt-1 block w-full" />
                </div>
                <div class="w-full md:w-40">
                    <x-input-label value="Type" />
                    <select wire:model.live="typeFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All</option>
                        <option value="full">Full</option>
                        <option value="mcq">MCQ</option>
                        <option value="cq">CQ</option>
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
                    <x-secondary-button type="button" wire:click="exportCsv">
                        Export
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">Student</th>
                        <th class="px-3 py-2 text-left">Section</th>
                        <th class="px-3 py-2 text-left">Year</th>
                        <th class="px-3 py-2 text-left">Model Test</th>
                        <th class="px-3 py-2 text-left">Subject</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Marks</th>
                        <th class="px-3 py-2 text-left">Grade</th>
                        <th class="px-3 py-2 text-left">Grade Point</th>
                        <th class="px-3 py-2 text-left">Final Grade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($results as $row)
                        @php
                            $test = $row->test;
                            $student = $row->student;
                            $subjectKey = $row->subject ?? $test?->subject;
                            $subjectLabel = $subjectOptions[$subjectKey] ?? ($subjectKey ?? '—');
                            $markParts = [];
                            if ($test?->type !== 'cq') {
                                $markParts[] = 'MCQ: ' . ($row->mcq_mark !== null ? number_format($row->mcq_mark, 2) : '–');
                            }
                            if ($test?->type !== 'mcq') {
                                $markParts[] = 'CQ: ' . ($row->cq_mark !== null ? number_format($row->cq_mark, 2) : '–');
                            }
                            if ($test?->type === 'full' && $row->practical_mark !== null) {
                                $markParts[] = 'Practical: ' . number_format($row->practical_mark, 2);
                            }
                            $finalIsFail = $finalGradeFailMap[$row->model_test_student_id] ?? false;
                            $finalGrade = $finalIsFail ? 'F' : ($row->grade ?? '—');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-900">{{ $student?->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $student?->contact_number }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $student?->section }}</td>
                            <td class="px-3 py-2">{{ $row->year }}</td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-800">{{ $test?->name }}</div>
                                <div class="text-xs text-gray-500">{{ $test?->year }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $subjectLabel }}</td>
                            <td class="px-3 py-2 capitalize">{{ $test?->type }}</td>
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
                            <td class="px-3 py-2">
                                <span class="{{ $finalIsFail ? 'text-red-700 font-semibold' : 'text-gray-800' }}">
                                    {{ $finalGrade }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-4 text-center text-gray-500">No results found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $results->links() }}
        </div>
    </div>
</div>
