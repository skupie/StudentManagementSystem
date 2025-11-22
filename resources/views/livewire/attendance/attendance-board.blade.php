<div class="bg-white shadow rounded-lg p-6 space-y-6">
    <div class="grid md:grid-cols-5 gap-4">
        <div>
            <x-input-label value="Class" />
            <select wire:model.live="selectedClass" class="mt-1 block w-full rounded-md border-gray-300">
                @foreach ($classOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Section" />
            <select wire:model.live="selectedSection" class="mt-1 block w-full rounded-md border-gray-300">
                @foreach ($sectionOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Attendance Date" />
            <x-text-input type="date" wire:model.live="attendanceDate" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label value="Search Student" />
            <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Name" />
        </div>
        <div class="flex items-end">
            <span class="text-sm text-gray-500">Active students: {{ $students->count() }}</span>
        </div>
    </div>

    @if ($isWeekend)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
            Friday is treated as a weekend. Attendance cannot be recorded on this day.
        </div>
    @elseif ($isHoliday)
        <div class="bg-pink-50 border border-pink-200 text-pink-800 rounded-lg p-4">
            Today is marked as a holiday. Attendance cannot be recorded on this day.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Student</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Note</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($students as $student)
                        @php($record = $records->get($student->id))
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $student->name }}</div>
                                <div class="text-xs text-gray-500">{{ $student->phone_number }}</div>
                            </td>
                            <td class="px-4 py-2">
                                @if ($record)
                                    <span class="px-3 py-1 rounded-full text-xs {{ $record->status === 'present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">Not marked</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 whitespace-pre-line">
                                {{ $record?->note }}
                            </td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <x-secondary-button wire:click="markAttendance({{ $student->id }}, 'present')" type="button" class="text-xs">
                                    Present
                                </x-secondary-button>
                                <x-secondary-button wire:click="markAttendance({{ $student->id }}, 'absent')" type="button" class="text-xs">
                                    Absent
                                </x-secondary-button>
                                <x-secondary-button wire:click="openNoteForm({{ $student->id }})" type="button" class="text-xs">
                                    Note
                                </x-secondary-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                No students found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($noteStudentId)
            <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold text-gray-700">Add absence note</h3>
                    <button type="button" class="text-sm text-gray-500" wire:click="$set('noteStudentId', null)">Close</button>
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label value="Category" />
                        <select wire:model="noteCategory" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Select category</option>
                            @foreach ($absenceCategories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label value="Details" />
                        <textarea wire:model="noteBody" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                    </div>
                </div>
                <div class="text-right">
                    <x-primary-button wire:click="saveNote" type="button">
                        Save Note
                    </x-primary-button>
                </div>
            </div>
        @endif
    @endif
</div>
