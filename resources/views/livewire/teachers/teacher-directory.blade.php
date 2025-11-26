<div class="space-y-4">
    <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="font-semibold text-gray-800">Teacher Information</h3>
            <p class="text-sm text-gray-500">Call directly from the list. Payment hidden for Administrative Assistants.</p>
        </div>
        <div class="w-full md:w-64">
            <x-input-label value="Search by name or subject" />
            <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Search..." />
        </div>
    </div>

    @if ($canCreate)
        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-gray-800">{{ $editingId ? 'Edit Teacher' : 'Add Teacher' }}</h4>
                @if ($editingId)
                    <x-secondary-button type="button" wire:click="resetForm">Cancel</x-secondary-button>
                @endif
            </div>
            <div class="grid md:grid-cols-3 gap-3">
                <div>
                    <x-input-label value="Name" />
                    <x-text-input type="text" wire:model.defer="form.name" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('form.name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Subject" />
                    <x-text-input type="text" wire:model.defer="form.subject" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('form.subject')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Payment" />
                    <x-text-input type="number" step="0.01" wire:model.defer="form.payment" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('form.payment')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Contact Number" />
                    <x-text-input type="text" wire:model.defer="form.contact_number" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('form.contact_number')" class="mt-1" />
                </div>
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="save">
                    {{ $editingId ? 'Update Teacher' : 'Save Teacher' }}
                </x-primary-button>
            </div>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Subject</th>
                    @unless($hidePayment)
                        <th class="px-4 py-2">Payment</th>
                    @endunless
                    <th class="px-4 py-2">Contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($teachers as $teacher)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-semibold text-gray-900">
                            {{ $teacher->name }}
                        </td>
                        <td class="px-4 py-2">{{ $teacher->subject ?? '—' }}</td>
                        @unless($hidePayment)
                            <td class="px-4 py-2">{{ $teacher->payment ? number_format($teacher->payment, 2) : '—' }}</td>
                        @endunless
                        <td class="px-4 py-2 space-x-2">
                            @if ($teacher->contact_number)
                                <a href="tel:{{ $teacher->contact_number }}" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700">
                                    <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs">Call</span>
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                            @if ($canCreate)
                                <x-secondary-button type="button" class="text-xs" wire:click="edit({{ $teacher->id }})">
                                    Edit
                                </x-secondary-button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $hidePayment ? 3 : 4 }}" class="px-4 py-6 text-center text-gray-500">
                            No teachers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $teachers->links() }}
    </div>
</div>
