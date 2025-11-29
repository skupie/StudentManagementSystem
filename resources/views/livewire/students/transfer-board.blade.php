<div class="bg-white shadow rounded-lg p-6 space-y-6">
    @if ($alert)
        <div class="p-3 rounded-md bg-green-50 text-green-800 border border-green-200">
            {{ $alert }}
        </div>
    @endif
    <div class="space-y-2">
        <h2 class="text-xl font-semibold text-gray-800">Transfer Students</h2>
        <p class="text-sm text-gray-600">Move all HSC 1st Year students to HSC 2nd Year while keeping their sections unchanged.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="p-4 border rounded-lg bg-gray-50">
            <p class="text-sm text-gray-700 font-semibold">Current HSC 1st Year (by section)</p>
            <div class="mt-2 space-y-1 text-sm text-gray-700">
                @forelse ($hscOneCounts as $section => $count)
                    <div class="flex justify-between">
                        <span>{{ \App\Support\AcademyOptions::sectionLabel($section) }}</span>
                        <span class="font-semibold">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No students found.</p>
                @endforelse
            </div>
        </div>
        <div class="p-4 border rounded-lg bg-indigo-50 border-indigo-100">
            <p class="text-sm text-indigo-800 font-semibold">Current HSC 2nd Year (by section)</p>
            <div class="mt-2 space-y-1 text-sm text-indigo-900">
                @forelse ($hscTwoCounts as $section => $count)
                    <div class="flex justify-between">
                        <span>{{ \App\Support\AcademyOptions::sectionLabel($section) }}</span>
                        <span class="font-semibold">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-indigo-700">No students found.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <x-primary-button
            type="button"
            wire:click="promptTransfer"
        >
            Promote All to HSC 2nd Year
        </x-primary-button>
        <x-secondary-button
            type="button"
            class="ml-3"
            wire:click="promptRevert"
        >
            Revert Promotion
        </x-secondary-button>
    </div>

    @if ($confirmingTransfer)
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Transfer</h3>
                    <button wire:click="cancelTransfer" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Are you sure to promote all students from <strong class="font-semibold text-gray-900">HSC 1st Year</strong> to <strong class="font-semibold text-red-600">HSC 2nd Year</strong>? Action cannot be undone!
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelTransfer">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="transfer">Confirm</x-danger-button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingRevert)
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Revert</h3>
                    <button wire:click="cancelRevert" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Revert promotion and move students back to <strong class="font-semibold text-gray-900">HSC 1st Year</strong>? Action cannot be undone!
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelRevert">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="revert">Confirm Revert</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
