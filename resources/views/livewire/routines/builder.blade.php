<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Create Routine Entry</h2>
                <p class="text-sm text-gray-500">Entries are saved against a specific date; tables below show the selected date (BST).</p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 md:items-end">
                <div>
                    <x-input-label value="View Date" />
                    <x-text-input type="date" wire:model.live="viewDate" class="mt-1 block w-full" />
                </div>
                <div class="flex gap-2 items-end">
                    <x-secondary-button type="button" wire:click="exportCsv">
                        Export CSV
                    </x-secondary-button>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="file" wire:model="importFile" class="hidden" id="routine-import">
                        <span class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white hover:bg-gray-50 cursor-pointer" onclick="document.getElementById('routine-import').click();">
                            Import CSV
                        </span>
                    </label>
                    @error('importFile') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
                    @if ($importFile)
                        <x-secondary-button type="button" wire:click="importCsv">
                            Upload
                        </x-secondary-button>
                    @endif
                </div>
            </div>
        </div>
        <div class="grid md:grid-cols-6 gap-4 items-end">
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
                <select wire:model.defer="form.section" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.section')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Date (BST)" />
                <x-text-input type="date" wire:model.defer="form.routine_date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.routine_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Time (BST)" />
                <x-text-input type="text" wire:model.defer="form.time_slot" class="mt-1 block w-full" placeholder="e.g. 10:00 AM" />
                <x-input-error :messages="$errors->get('form.time_slot')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Subject" />
                <x-text-input type="text" wire:model.defer="form.subject" class="mt-1 block w-full" placeholder="e.g. Physics" />
                <x-input-error :messages="$errors->get('form.subject')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Teacher" />
                <select wire:model.defer="form.teacher_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">
                            {{ $teacher->name }}
                            @if (!empty($teacher->subjects))
                                ({{ implode(', ', (array) $teacher->subjects) }})
                            @elseif (!empty($teacher->subject))
                                ({{ $teacher->subject }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.teacher_id')" class="mt-1" />
            </div>
        </div>
        <div class="flex justify-end gap-3">
            @if($editingId)
                <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
            @endif
            <x-primary-button type="button" wire:click="save">
                {{ $editingId ? __('Update Entry') : __('Add Entry') }}
            </x-primary-button>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        @foreach ($tables as $key => $table)
            <div class="bg-white shadow rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $table['class_label'] }} — {{ $table['section_label'] }}</h3>
                        <p class="text-xs text-gray-500">Table: {{ strtoupper(str_replace('|', '_', $key)) }} • Date: {{ \Carbon\Carbon::parse($viewDate)->timezone('Asia/Dhaka')->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left">Time</th>
                                <th class="px-3 py-2 text-left">Subject</th>
                                <th class="px-3 py-2 text-left">Teacher</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($table['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ $row->time_slot }}</td>
                                    <td class="px-3 py-2">{{ $row->subject }}</td>
                                    <td class="px-3 py-2">{{ $row->teacher?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <x-secondary-button type="button" wire:click="edit({{ $row->id }})" class="text-xs">
                                    {{ __('Edit') }}
                                </x-secondary-button>
                                <x-danger-button type="button" wire:click="promptDelete({{ $row->id }})" class="text-xs">
                                    {{ __('Delete') }}
                                </x-danger-button>
                            </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">No entries yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    @if (! is_null($confirmingDeleteId ?? null))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Deletion</h3>
                    <button wire:click="cancelDelete" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Do you want to delete this routine entry? This cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="deleteConfirmed">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
