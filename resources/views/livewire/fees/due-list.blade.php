<div class="bg-white shadow rounded-lg p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="grid md:grid-cols-4 gap-3 w-full md:w-auto">
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
                <x-input-label value="Year" />
                <x-text-input type="text" wire:model.debounce.500ms="yearFilter" class="mt-1 block w-full" placeholder="2024" />
            </div>
            <div>
                <x-input-label value="Student Name" />
                <x-text-input type="text" wire:model.live.debounce.300ms="nameFilter" class="mt-1 block w-full" placeholder="Search name" />
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Total Outstanding</div>
            <div class="text-2xl font-bold text-red-600">Tk {{ number_format($totalDue, 2) }}</div>
        </div>
    </div>

    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Showing {{ $students->count() }} students with dues
        </div>
        @unless ($embedded)
            <div class="space-x-2">
                <x-secondary-button type="button" wire:click="exportPdf">
                    Download PDF
                </x-secondary-button>
                <x-secondary-button type="button" wire:click="exportExcel">
                    Download Excel
                </x-secondary-button>
            </div>
        @endunless
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-2">Student</th>
                    <th class="px-4 py-2">Section</th>
                    <th class="px-4 py-2">Outstanding (Tk)</th>
                    <th class="px-4 py-2 text-right">Call</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="font-semibold text-gray-900">{{ $student->name }}</div>
                            <div class="text-xs text-gray-500">{{ $student->phone_number }}</div>
                        </td>
                        <td class="px-4 py-2">
                            {{ \App\Support\AcademyOptions::classLabel($student->class_level) }}
                            <div class="text-xs text-gray-500">{{ \App\Support\AcademyOptions::sectionLabel($student->section) }}</div>
                        </td>
                        <td class="px-4 py-2 text-red-600 font-semibold">Tk {{ number_format($student->outstanding, 2) }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="tel:{{ $student->phone_number }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-50">
                                Call
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            No dues found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
