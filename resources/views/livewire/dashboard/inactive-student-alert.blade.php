<div class="bg-white rounded-lg shadow p-4 space-y-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h3 class="font-semibold text-gray-800">Students Inactive / Limited Attendance ({{ $referenceLabel }})</h3>
        <div class="grid md:grid-cols-2 gap-2 w-full md:w-auto">
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="classFilter" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="sectionFilter" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <ul class="text-sm space-y-1">
        @forelse ($records as $record)
            <li>
                <span class="font-semibold text-gray-900">{{ $record['student']->name }}</span>
                <span class="text-xs text-gray-500">
                    ({{ \App\Support\AcademyOptions::classLabel($record['student']->class_level ?? '') }}
                    / {{ \App\Support\AcademyOptions::sectionLabel($record['student']->section ?? '') }})
                </span>
                — {{ $record['reason'] }}
            </li>
        @empty
            <li class="text-gray-500 text-sm">All students are active with sufficient attendance.</li>
        @endforelse
    </ul>
</div>
