<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-800">Add Weekly Exam Assignment</h3>

        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Exam Date" />
                <x-text-input type="date" wire:model.defer="form.exam_date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.exam_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Exam Name" />
                <x-text-input type="text" wire:model.defer="form.exam_name" class="mt-1 block w-full" placeholder="Weekly Physics Test" />
                <x-input-error :messages="$errors->get('form.exam_name')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Teacher" />
                <select wire:model.defer="form.teacher_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select Teacher</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.teacher_id')" class="mt-1" />
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Class" />
                <select wire:model.defer="form.class_level" class="mt-1 block w-full rounded-md border-gray-300">
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
            <div>
                <x-input-label value="Subject" />
                <select wire:model.defer="form.subject" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($subjectOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.subject')" class="mt-1" />
            </div>
        </div>

        <div class="text-right">
            <x-primary-button type="button" wire:click="save">Assign Weekly Exam</x-primary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Search Exam" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Exam name" />
            </div>
            <div>
                <x-input-label value="Date Filter" />
                <x-text-input type="date" wire:model.live="dateFilter" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="Teacher Filter" />
                <select wire:model.live="teacherFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All Teachers</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Exam</th>
                        <th class="px-4 py-2">Class/Section</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Teacher</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($assignments as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $item->exam_date?->format('d M Y') }}</td>
                            <td class="px-4 py-2 font-semibold text-gray-800">{{ $item->exam_name }}</td>
                            <td class="px-4 py-2">{{ \App\Support\AcademyOptions::classLabel($item->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($item->section) }}</td>
                            <td class="px-4 py-2">{{ \App\Support\AcademyOptions::subjectLabel($item->subject) }}</td>
                            <td class="px-4 py-2">{{ $item->teacher?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-right">
                                <x-danger-button type="button" class="text-xs" wire:click="delete({{ $item->id }})">Delete</x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No weekly exam assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $assignments->links() }}
    </div>
</div>
