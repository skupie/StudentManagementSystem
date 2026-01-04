<div class="space-y-4">
    <div class="bg-white shadow rounded-lg p-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-gray-800">
                    {{ $examFilter !== 'all' ? ($examOptions->firstWhere('id', (int) $examFilter)?->name . ' Results') : 'Model Test Results' }}
                </h2>
            </div>
            <div class="flex flex-col md:flex-row gap-3 md:items-end">
                <div class="w-full md:w-48">
                    <x-input-label value="Search" />
                    <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Filter by name/contact" />
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
                <div class="w-full md:w-48">
                    <x-input-label value="Exam" />
                    <select wire:model.live="examFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All</option>
                        @foreach ($examOptions as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">Student</th>
                        <th class="px-3 py-2 text-left">Section</th>
                        <th class="px-3 py-2 text-left">Final Grade</th>
                        <th class="px-3 py-2 text-left">GPA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($students as $stu)
                        @php $final = $finals[$stu->id] ?? ['grade' => null, 'point' => null]; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $stu->name }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $stu->section }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $final['grade'] === 'F' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $final['grade'] ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ $final['point'] !== null ? number_format($final['point'], 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-gray-500">No published results found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $students->links() }}
        </div>
    </div>

    @if (! $verified)
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Please Input your mobile to view results</h3>
                    <button wire:click="$set('verified', false)" class="text-gray-500 hover:text-gray-700" aria-label="Close">&times;</button>
                </div>
                <p class="text-sm text-gray-600">Enter your Mobile number and HSC Batch to continue.</p>
                <div class="space-y-2">
                    <x-input-label value="Mobile number" />
                    <x-text-input type="text" wire:model.defer="mobileInput" class="block w-full" placeholder="01XXXXXXXXX" />
                    @error('mobileInput') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <x-input-label value="HSC Batch (Year)" />
                    <x-text-input type="text" wire:model.defer="hscBatchInput" class="block w-full" placeholder="e.g., 2026" />
                    @error('hscBatchInput') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="verifyMobile">Verify</x-secondary-button>
                </div>
            </div>
        </div>
    @endif
</div>
