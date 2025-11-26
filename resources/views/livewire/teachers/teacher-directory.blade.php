<div class="space-y-4">
    <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="font-semibold text-gray-800">Teacher Information</h3>
            <p class="text-sm text-gray-500">Call directly from the list. Payment hidden for Administrative Assistants.</p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto md:items-end">
            <div class="w-full md:w-64">
                <x-input-label value="Search" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Search..." />
            </div>
            <div class="w-full md:w-48">
                <x-input-label value="Status" />
                <select wire:model.live="statusFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
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
                <div class="md:col-span-3">
                    <x-input-label value="Note" />
                    <textarea wire:model.defer="form.note" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                    <x-input-error :messages="$errors->get('form.note')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Status" />
                    <select wire:model.defer="form.is_active" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <x-input-error :messages="$errors->get('form.is_active')" class="mt-1" />
                </div>
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="save">
                    {{ $editingId ? 'Update Teacher' : 'Save Teacher' }}
                </x-primary-button>
            </div>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto" x-data="{ noteModalOpen: false, noteText: '', noteName: '' }" @open-note.window="noteModalOpen = true; noteText = $event.detail.note; noteName = $event.detail.name">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Subject</th>
                    <th class="px-4 py-2">Status</th>
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
                            @php
                                $noteText = $teacher->note && trim($teacher->note) !== '' ? $teacher->note : 'No Additional Information Available!';
                            @endphp
                            <button type="button" class="underline" wire:click="$dispatch('open-note', { note: @js($noteText), name: @js($teacher->name) })">
                                {{ $teacher->name }}
                            </button>
                        </td>
                        <td class="px-4 py-2">{{ $teacher->subject ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded-full text-xs {{ $teacher->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $teacher->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
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
                        <td colspan="{{ $hidePayment ? 4 : 5 }}" class="px-4 py-6 text-center text-gray-500">
                            No teachers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $teachers->links() }}

        <div x-show="noteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40" style="display: none;">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 space-y-3">
                <div class="flex justify-between items-center">
                    <div class="font-semibold text-gray-800" x-text="noteName"></div>
                    <button type="button" class="text-gray-500" @click="noteModalOpen = false">Close</button>
                </div>
                <div class="text-sm text-gray-700 whitespace-pre-line" x-text="noteText"></div>
            </div>
        </div>
    </div>
</div>
