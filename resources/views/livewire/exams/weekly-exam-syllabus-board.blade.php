<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-800">Add Weekly Exam Syllabus</h3>
        @if ($isTeacherRole)
            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                You can add syllabus anytime, but exam date must be selected from your assigned weekly exam dates.
            </div>
            @if (! $teacherLinked)
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    No teacher profile is linked with this login.
                </div>            
            @elseif (empty($examDateOptions))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    No weekly exam date is assigned to your name yet.
                </div>
            @elseif (empty($subjectOptions))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    No subject is assigned to your teacher profile for this section.
                </div>
            @endif
        @endif

        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="{{ $isTeacherRole ? 'Exam Date' : 'Week Start Date' }}" />
                @if ($isTeacherRole)
                    <select wire:model.defer="form.week_start_date" class="mt-1 block w-full rounded-md border-gray-300">
                        @forelse ($examDateOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @empty
                            <option value="">No exam date available</option>
                        @endforelse
                    </select>
                @else
                    <x-text-input type="date" wire:model.defer="form.week_start_date" class="mt-1 block w-full" />
                @endif
                <x-input-error :messages="$errors->get('form.week_start_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Title" />
                <x-text-input type="text" wire:model.defer="form.title" class="mt-1 block w-full" placeholder="Week 3 Syllabus" />
                <x-input-error :messages="$errors->get('form.title')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Subject" />
                <select wire:model.defer="form.subject" class="mt-1 block w-full rounded-md border-gray-300">
                    @forelse ($subjectOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @empty
                        <option value="">No subject available</option>
                    @endforelse
                </select>
                <x-input-error :messages="$errors->get('form.subject')" class="mt-1" />
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
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
        </div>

        <div>
            <x-input-label value="Syllabus Details" />
            <textarea wire:model.defer="form.syllabus_details" rows="4" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Enter weekly exam syllabus details..."></textarea>
            <x-input-error :messages="$errors->get('form.syllabus_details')" class="mt-1" />
        </div>

        <div class="text-right">
            <x-primary-button type="button" wire:click="save" :disabled="$isTeacherRole && (empty($subjectOptions) || empty($examDateOptions))">
                Save Syllabus
            </x-primary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Search" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Title or details" />
            </div>
            <div>
                <x-input-label value="Class Filter" />
                <select wire:model.live="classFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($filterClassOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section Filter" />
                <select wire:model.live="sectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($filterSectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Week</th>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Class/Section</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Details</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($syllabi as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $row->week_start_date?->format('d M Y') }}</td>
                            <td class="px-4 py-2 font-semibold text-gray-800">{{ $row->title }}</td>
                            <td class="px-4 py-2">{{ \App\Support\AcademyOptions::classLabel($row->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($row->section) }}</td>
                            <td class="px-4 py-2">{{ \App\Support\AcademyOptions::subjectLabel($row->subject) }}</td>
                            <td class="px-4 py-2 max-w-sm truncate" title="{{ $row->syllabus_details }}">{{ $row->syllabus_details }}</td>
                            <td class="px-4 py-2 text-right">
                                <x-danger-button type="button" class="text-xs" wire:click="delete({{ $row->id }})">Delete</x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No syllabus entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $syllabi->links() }}
    </div>
</div>
