<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Attendance Snapshot</h3>
                <p class="text-sm text-gray-500">{{ $dateLabel }}</p>
            </div>
            <div class="grid md:grid-cols-3 gap-3 w-full lg:w-auto">
                <div>
                    <x-input-label value="Date" />
                    <x-text-input type="date" wire:model.live="filterDate" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label value="Class" />
                    <select wire:model.live="filterClass" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($classOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Section" />
                    <select wire:model.live="filterSection" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($sectionOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="p-4 rounded-lg bg-green-50">
                <div class="text-sm text-gray-500">Present</div>
                <div class="text-3xl font-bold text-green-700 mt-1">{{ $totals['present'] ?? 0 }}</div>
            </div>
            <div class="p-4 rounded-lg bg-red-50">
                <div class="text-sm text-gray-500">Absent</div>
                <div class="text-3xl font-bold text-red-700 mt-1">{{ $totals['absent'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-base font-semibold text-gray-800">Absence Information</h4>
            <div class="text-sm text-gray-500">
                Total Absent: {{ $totals['absent'] ?? 0 }}
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2 text-left">Student</th>
                        <th class="px-4 py-2 text-left">Class / Section</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Absence Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($absentRecords as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $record->student->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $record->student->phone_number ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ \App\Support\AcademyOptions::classLabel($record->student->class_level ?? '') }}</div>
                                <div class="text-xs text-gray-500">{{ \App\Support\AcademyOptions::sectionLabel($record->student->section ?? '') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                    Absent
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                <div class="font-semibold text-gray-800">{{ $record->category ?? 'Reason not set' }}</div>
                                <div class="text-gray-600">
                                    {{ $record->note ? $record->note : 'No additional note provided.' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No absent students for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
