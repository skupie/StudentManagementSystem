<div class="space-y-7">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 md:p-7 shadow-sm space-y-6">

        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Lecture Note' : 'Upload Lecture Notes' }}</h3>
                <p class="text-xs text-slate-500">Share topic files with selected classes and sections.</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($editingId)
                    <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                        Edit Mode
                    </span>
                    <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
                @endif
            </div>
        </div>
        @if ($isTeacherRole && ! $teacherLinked)
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                No teacher profile is linked with this login.
            </div>
        @elseif ($isTeacherRole && empty($subjectOptions))
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                No subject is assigned to your teacher profile. Contact admin.
            </div>
        @endif

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Title" />
                <x-text-input type="text" wire:model.defer="form.title" class="mt-1 block w-full border-slate-200 bg-white/90 focus:border-indigo-400 focus:ring-indigo-200" placeholder="Chapter / Topic" />
                <x-input-error :messages="$errors->get('form.title')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Subject" />
                <select wire:model.live="form.subject" class="mt-1 block w-full rounded-md border-slate-200 bg-white/90 focus:border-indigo-400 focus:ring-indigo-200">
                    @if (! $isTeacherRole)
                        <option value="">General</option>
                    @endif
                    @foreach ($subjectOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.subject')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="File" />
                <input type="file" wire:model="uploadFile" class="mt-1 block w-full rounded-md border-slate-200 bg-white/90 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-100 file:px-3 file:py-2 file:text-indigo-700" />
                <p wire:loading wire:target="uploadFile" class="mt-1 text-xs font-semibold text-indigo-600">Uploading file, please wait...</p>
                <x-input-error :messages="$errors->get('uploadFile')" class="mt-1" />
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Classes (Select multiple)" />
                <div class="mt-1 rounded-xl border border-slate-200 bg-white/80 p-3 max-h-40 overflow-y-auto space-y-1.5">
                    @foreach ($classOptions as $key => $label)
                        <label class="flex items-center gap-2 rounded-md px-2 py-1 text-sm text-slate-700 hover:bg-indigo-50">
                            <input type="checkbox" wire:model.defer="form.class_levels" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('form.class_levels')" class="mt-1" />
                <x-input-error :messages="$errors->get('form.class_levels.*')" class="mt-1" />
            </div>

            <div>
                <x-input-label value="Sections" />
                @if ($isTeacherRole && ! $teacherCanChooseSections)
                    <div class="mt-1 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-emerald-700">Auto-selected</div>
                        <div class="flex flex-wrap gap-2">
                        @forelse ($sectionOptions as $key => $label)
                            <span class="inline-flex items-center rounded-full border border-emerald-300 bg-white px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ $label }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-500">No section available for selected subject.</span>
                        @endforelse
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Sections are auto-selected from your assigned subject and cannot be changed.</p>
                @else
                    <div class="mt-1 rounded-xl border border-indigo-100 bg-indigo-50/40 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
                                Selected: {{ count($form['sections'] ?? []) }}
                            </span>
                            @if (! empty($sectionOptions))
                                <button
                                    type="button"
                                    wire:click="$set('form.sections', @js(array_keys($sectionOptions)))"
                                    class="rounded-md border border-indigo-200 bg-white px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                >
                                    Select All
                                </button>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <div class="min-h-[32px] rounded-md border border-indigo-100 bg-white px-2 py-2">
                                <div class="flex flex-wrap gap-2">
                                    @forelse (($form['sections'] ?? []) as $sectionKey)
                                        <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                            {{ $sectionOptions[$sectionKey] ?? \App\Support\AcademyOptions::sectionLabel((string) $sectionKey) }}
                                            <button
                                                type="button"
                                                wire:click="removeSection('{{ $sectionKey }}')"
                                                class="text-indigo-500 hover:text-indigo-700"
                                                aria-label="Remove section"
                                            >
                                                x
                                            </button>
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500">No section selected yet.</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-md border border-indigo-200 bg-white">
                                <div class="px-3 py-2">
                                    <x-text-input
                                        type="text"
                                        wire:model.live.debounce.250ms="sectionSearch"
                                        class="block w-full border-0 p-0 focus:ring-0"
                                        placeholder="Search section and click to add..."
                                    />
                                </div>
                                @if (trim((string) ($sectionSearch ?? '')) !== '')
                                    <div class="border-t border-indigo-100 max-h-40 overflow-y-auto p-2 space-y-1">
                                        @forelse ($filteredSectionOptions as $key => $label)
                                            <button
                                                type="button"
                                                wire:click="addSection('{{ $key }}')"
                                                class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm text-gray-700 hover:bg-indigo-50"
                                            >
                                                <span>{{ $label }}</span>
                                                <span class="text-[11px] text-indigo-600">Add</span>
                                            </button>
                                        @empty
                                            <div class="px-2 py-1 text-xs text-gray-500">No matching section found.</div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                <x-input-error :messages="$errors->get('form.sections')" class="mt-1" />
                <x-input-error :messages="$errors->get('form.sections.*')" class="mt-1" />
            </div>

            <div class="md:col-span-1">
                <x-input-label value="Description" />
                <x-text-input type="text" wire:model.defer="form.description" class="mt-1 block w-full border-slate-200 bg-white/90 focus:border-indigo-400 focus:ring-indigo-200" placeholder="Optional note" />
                <x-input-error :messages="$errors->get('form.description')" class="mt-1" />
            </div>
        </div>

        <div class="flex items-center justify-end pt-1">
            <x-primary-button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="uploadFile,save"
                :disabled="$isTeacherRole && empty($subjectOptions)"
            >
                {{ $editingId ? 'Update Note' : 'Upload Note' }}
            </x-primary-button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 md:p-7 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Note Library</h4>
            <span class="text-xs text-slate-500">Search and manage uploaded files</span>
        </div>
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Search" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full border-slate-200 focus:border-indigo-400 focus:ring-indigo-200" placeholder="Title" />
            </div>
            <div>
                <x-input-label value="Class Filter" />
                <select wire:model.live="classFilter" class="mt-1 block w-full rounded-md border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                    @foreach ($filterClassOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section Filter" />
                <select wire:model.live="sectionFilter" class="mt-1 block w-full rounded-md border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                    @foreach ($filterSectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Class / Section</th>
                        <th class="px-4 py-2">Uploaded By</th>
                        <th class="px-4 py-2">File</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($notes as $note)
                        <tr class="hover:bg-indigo-50/40 transition-colors">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-slate-900">{{ $note->title }}</div>
                                @if ($note->description)
                                    <div class="text-xs text-slate-500">{{ $note->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $note->subject ? \App\Support\AcademyOptions::subjectLabel($note->subject) : 'General' }}</td>
                            <td class="px-4 py-2">
                                <div class="text-xs font-semibold text-slate-500">Classes</div>
                                <div>
                                    {{ collect($note->classTargets())->map(fn ($key) => \App\Support\AcademyOptions::classLabel($key))->implode(', ') }}
                                </div>
                                <div class="text-xs font-semibold text-slate-500 mt-1">Sections</div>
                                <div class="text-xs text-slate-500">
                                    {{ collect($note->sectionTargets())->map(fn ($key) => \App\Support\AcademyOptions::sectionLabel($key))->implode(', ') }}
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ $note->uploader?->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-500">{{ $note->created_at?->format('d M Y') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <a href="{{ route('teacher.notes.file', ['teacherNote' => $note->id, 'v' => $note->updated_at?->timestamp]) }}" target="_blank" class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200">
                                    {{ $note->original_name }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-right space-x-2">
                                @php($canEditNote = $this->canEditNote($note))
                                @php($canDeleteNote = $this->canDeleteNote($note))

                                @if ($canEditNote)
                                    <x-secondary-button type="button" wire:click="edit({{ $note->id }})" class="text-xs">
                                        Edit
                                    </x-secondary-button>
                                @endif

                                @if ($canDeleteNote)
                                    <x-danger-button type="button" wire:click="promptDelete({{ $note->id }})" class="text-xs">
                                        Delete
                                    </x-danger-button>
                                @endif

                                @if (! $canEditNote && ! $canDeleteNote)
                                    <span class="text-xs text-gray-400">No actions</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No uploaded notes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $notes->links() }}
    </div>

    @if (! is_null($confirmingDeleteId))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-md mx-auto p-6 space-y-4 border border-slate-200">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Confirm Deletion</h3>
                    <button wire:click="cancelDelete" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-slate-700">
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
