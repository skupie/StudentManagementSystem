<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-800">Create Absence Note</h3>
                <p class="text-sm text-gray-500">Select an absent student, then record the note.</p>
                <p class="text-sm font-semibold text-gray-700 mt-1">Absent Students: {{ $absentCount }}</p>
            </div>
            <div class="grid md:grid-cols-3 gap-3 w-full md:w-auto">
                <div>
                    <x-input-label value="Date" />
                    <x-text-input type="date" wire:model.live="absenceDate" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label value="Class" />
                    <select wire:model.live="absenceClass" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($classOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Section" />
                    <select wire:model.live="absenceSection" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($sectionOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2 text-left">Student</th>
                        <th class="px-4 py-2 text-left">Class / Section</th>
                        <th class="px-4 py-2 text-left">Reason</th>
                        <th class="px-4 py-2 text-left">Note</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($absentStudents as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $record->student->name ?? 'Student' }}</div>
                                <div class="text-xs text-gray-500">{{ optional($record->attendance_date)->format('d M Y') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ \App\Support\AcademyOptions::classLabel($record->student->class_level ?? '') }}</div>
                                <div class="text-xs text-gray-500">{{ \App\Support\AcademyOptions::sectionLabel($record->student->section ?? '') }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $record->category ?? 'Not set' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $record->note ?? 'No note available' }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <a href="tel:{{ $record->student->phone_number }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-50">
                                    {{ __('Call') }}
                                </a>
                                <x-secondary-button type="button" wire:click="selectAbsentStudent({{ $record->id }})" class="text-xs">
                                    Add Note
                                </x-secondary-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                No absent students found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activeAttendanceId && $form['student_id'])
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">Note Details</h4>
                            <p class="text-sm text-gray-500">Student: {{ $activeStudentName }}</p>
                        </div>
                        <button class="text-gray-500 hover:text-gray-700 text-xl leading-none" wire:click="cancelNote">×</button>
                    </div>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Note Date" />
                            <x-text-input type="date" wire:model.defer="form.note_date" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('form.note_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Category" />
                            <select wire:model.defer="form.category" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('form.category')" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <x-input-label value="Notes" />
                        <textarea wire:model.defer="form.body" rows="4" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <x-secondary-button type="button" wire:click="cancelNote">Cancel</x-secondary-button>
                        <x-primary-button type="button" wire:click="saveNote">Save Note</x-primary-button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-4 gap-3">
            <div>
                <x-input-label value="Search Student" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Name" />
            </div>
            <div>
                <x-input-label value="Date" />
                <x-text-input type="date" wire:model.live="dateFilter" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="classFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="sectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Student</th>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Notes</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($notes as $note)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $note->student->name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \App\Support\AcademyOptions::classLabel($note->student->class_level ?? '') }}
                                    • {{ \App\Support\AcademyOptions::sectionLabel($note->student->section ?? '') }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $note->student->phone_number }}</div>
                            </td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($note->note_date)->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ $note->category }}</td>
                            <td class="px-4 py-2 whitespace-pre-line">{{ $note->body }}</td>
                            <td class="px-4 py-2 text-right">
                                <x-danger-button type="button" wire:click="delete({{ $note->id }})" class="text-xs">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                No notes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $notes->links() }}
    </div>
</div>
