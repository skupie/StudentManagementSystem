<div class="bg-white shadow rounded-lg p-6 space-y-6">
    @if ($alert)
        <div class="p-3 rounded-md bg-green-50 text-green-800 border border-green-200">
            {{ $alert }}
        </div>
    @endif

    @if (! $pinVerified)
        <div class="max-w-md mx-auto space-y-4">
            <div class="space-y-1 text-center">
                <h2 class="text-xl font-semibold text-gray-800">Transfer Access</h2>
                <p class="text-sm text-gray-600">Enter the PIN to access transfer actions.</p>
            </div>
            <div class="space-y-2">
                <x-input-label value="PIN Code" />
                <x-text-input type="password" wire:model.defer="pinInput" class="mt-1 block w-full" />
            </div>
            <div class="flex justify-center gap-3">
                <x-primary-button type="button" wire:click="verifyPin">Verify PIN</x-primary-button>
            </div>
        </div>
    @else
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

        <div class="border-t pt-6 space-y-3">
            <h3 class="text-lg font-semibold text-gray-800">Mark HSC 2nd Year as Passed</h3>
            <p class="text-sm text-gray-600">Mark every current HSC 2nd Year student as passed. Revert will undo this pass action for the same batch.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="p-4 border rounded-lg bg-gray-50">
                    <p class="text-sm text-gray-700 font-semibold">Current HSC 2nd Year (by Section)</p>
                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                        @forelse ($hscTwoCounts as $section => $count)
                            <div class="flex justify-between">
                                <span>{{ \App\Support\AcademyOptions::sectionLabel($section) }}</span>
                                <span class="font-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500">No students found.</p>
                        @endforelse
                    </div>
                </div>
                <div class="p-4 border rounded-lg bg-green-50 border-green-100">
                    <p class="text-sm text-green-800 font-semibold">Current Graduated Students (by Section)</p>
                    <div class="mt-2 space-y-1 text-sm text-green-900">
                        @forelse ($graduatedCounts as $section => $count)
                            <div class="flex justify-between">
                                <span>{{ \App\Support\AcademyOptions::sectionLabel($section) }}</span>
                                <span class="font-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-green-700">No graduated students found.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-primary-button type="button" wire:click="promptPassAll">
                    Mark All HSC 2nd Year as Passed
                </x-primary-button>
                <x-secondary-button type="button" wire:click="promptUnpass">
                    Revert Passed Status
                </x-secondary-button>
            </div>
        </div>
    @endif

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

    @if ($confirmingPassAll)
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Pass All</h3>
                    <button wire:click="cancelPassAll" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Mark all <strong class="font-semibold text-gray-900">HSC 2nd Year</strong> students as <strong class="font-semibold text-red-600">Passed</strong>? Action cannot be undone!
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelPassAll">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="passAll">Confirm</x-danger-button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingUnpass)
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Revert Pass</h3>
                    <button wire:click="cancelUnpass" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Revert the last batch marked as <strong class="font-semibold text-red-600">Passed</strong> back to active status?
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelUnpass">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="revertPassed">Confirm Revert</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
