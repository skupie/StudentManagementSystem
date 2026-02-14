<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">{{ $editingId ? 'Edit Lecture Note' : 'Upload Lecture Notes' }}</h3>
            @if ($editingId)
                <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
            @endif
        </div>

        <div class="grid md:grid-cols-2 gap-3">
            <div>
                <x-input-label value="Title" />
                <x-text-input type="text" wire:model.defer="form.title" class="mt-1 block w-full" placeholder="Chapter / Topic" />
                <x-input-error :messages="$errors->get('form.title')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="File" />
                <input type="file" wire:model="uploadFile" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                <x-input-error :messages="$errors->get('uploadFile')" class="mt-1" />
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Classes (Select multiple)" />
                <div class="mt-1 border rounded-md border-gray-300 p-3 max-h-36 overflow-y-auto space-y-1">
                    @foreach ($classOptions as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.defer="form.class_levels" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('form.class_levels')" class="mt-1" />
                <x-input-error :messages="$errors->get('form.class_levels.*')" class="mt-1" />
            </div>

            <div>
                <x-input-label value="Sections (Select multiple)" />
                <div class="mt-1 border rounded-md border-gray-300 p-3 max-h-36 overflow-y-auto space-y-1">
                    @foreach ($sectionOptions as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.defer="form.sections" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('form.sections')" class="mt-1" />
                <x-input-error :messages="$errors->get('form.sections.*')" class="mt-1" />
            </div>

            <div class="md:col-span-1">
                <x-input-label value="Description" />
                <x-text-input type="text" wire:model.defer="form.description" class="mt-1 block w-full" placeholder="Optional note" />
                <x-input-error :messages="$errors->get('form.description')" class="mt-1" />
            </div>
        </div>

        <div class="text-right">
            <x-primary-button type="button" wire:click="save">
                {{ $editingId ? 'Update Note' : 'Upload Note' }}
            </x-primary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Search" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Title" />
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
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Class / Section</th>
                        <th class="px-4 py-2">Uploaded By</th>
                        <th class="px-4 py-2">File</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($notes as $note)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $note->title }}</div>
                                @if ($note->description)
                                    <div class="text-xs text-gray-500">{{ $note->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-xs font-semibold text-gray-500">Classes</div>
                                <div>
                                    {{ collect($note->classTargets())->map(fn ($key) => \App\Support\AcademyOptions::classLabel($key))->implode(', ') }}
                                </div>
                                <div class="text-xs font-semibold text-gray-500 mt-1">Sections</div>
                                <div class="text-xs text-gray-500">
                                    {{ collect($note->sectionTargets())->map(fn ($key) => \App\Support\AcademyOptions::sectionLabel($key))->implode(', ') }}
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ $note->uploader?->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $note->created_at?->format('d M Y') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <a href="{{ route('teacher.notes.file', $note) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">
                                    {{ $note->original_name }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <x-secondary-button type="button" wire:click="edit({{ $note->id }})" class="text-xs">
                                    Edit
                                </x-secondary-button>
                                <x-danger-button type="button" wire:click="promptDelete({{ $note->id }})" class="text-xs">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No uploaded notes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $notes->links() }}
    </div>

    @if (! is_null($confirmingDeleteId))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Deletion</h3>
                    <button wire:click="cancelDelete" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Do you want to delete this lecture note: <span class="font-semibold">{{ $confirmingDeleteTitle }}</span>?
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="deleteConfirmed">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
