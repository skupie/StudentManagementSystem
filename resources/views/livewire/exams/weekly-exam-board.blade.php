<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-5 gap-3">
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
            <div>
                <x-input-label value="Subject" />
                <select wire:model.live="subjectFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($subjectOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Exam Date Filter" />
                <x-text-input type="date" wire:model.live="examDateFilter" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="Search Student" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Name" />
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-800">{{ $editingId ? 'Update Weekly Mark' : 'Add Weekly Mark' }}</h3>
        <div class="grid md:grid-cols-5 gap-3">
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="form.class_level" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.class_level')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="form.section" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.section')" class="mt-1" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Student" />
                <select wire:model.defer="form.student_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->phone_number }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.student_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Exam Date" />
                <x-text-input type="date" wire:model.defer="form.exam_date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.exam_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Subject" />
                <select wire:model.defer="form.subject" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($subjectList as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.subject')" class="mt-1" />
            </div>
        </div>
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Marks Obtained" />
                <x-text-input type="number" wire:model.defer="form.marks_obtained" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.marks_obtained')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Max Marks" />
                <x-text-input type="number" wire:model.defer="form.max_marks" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.max_marks')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Remarks" />
                <x-text-input type="text" wire:model.defer="form.remarks" class="mt-1 block w-full" />
            </div>
        </div>
        <div class="text-right space-x-2">
            @if ($editingId)
                <x-secondary-button type="button" wire:click="resetForm">Cancel</x-secondary-button>
            @endif
            <x-primary-button type="button" wire:click="save">
                {{ $editingId ? 'Update' : 'Save' }}
            </x-primary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Student</th>
                        <th class="px-4 py-2">Exam Date</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Marks</th>
                        <th class="px-4 py-2">Remarks</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($marks as $mark)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $mark->student->name }}</div>
                                <div class="text-xs text-gray-500">{{ $mark->student->phone_number }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $mark->exam_date->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ \App\Support\AcademyOptions::subjectLabel($mark->subject) }}</td>
                            <td class="px-4 py-2">
                                {{ $mark->marks_obtained }} / {{ $mark->max_marks }}
                                <div class="text-xs text-gray-500">
                                    {{ round(($mark->marks_obtained / $mark->max_marks) * 100, 1) }}%
                                </div>
                            </td>
                            <td class="px-4 py-2">{{ $mark->remarks }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <x-secondary-button type="button" wire:click="edit({{ $mark->id }})" class="text-xs">
                                    Edit
                                </x-secondary-button>
                                <x-danger-button type="button" wire:click="delete({{ $mark->id }})" class="text-xs">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                No marks found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $marks->links() }}
    </div>
</div>
