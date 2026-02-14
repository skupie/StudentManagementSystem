<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student Notes') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow rounded-lg p-4">
                <form method="GET" class="grid md:grid-cols-3 gap-3">
                    <div>
                        <x-input-label value="Class" />
                        <select name="class" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All Classes</option>
                            @foreach ($classOptions as $key => $label)
                                <option value="{{ $key }}" @selected($class === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Section" />
                        <select name="section" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All Sections</option>
                            @foreach ($sectionOptions as $key => $label)
                                <option value="{{ $key }}" @selected($section === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <x-primary-button type="submit">Filter Notes</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <th class="px-4 py-2">Title</th>
                                <th class="px-4 py-2">Class / Section</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">File</th>
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
                                        <div>{{ collect($note->classTargets())->map(fn ($key) => \App\Support\AcademyOptions::classLabel($key))->implode(', ') }}</div>
                                        <div class="text-xs font-semibold text-gray-500 mt-1">Sections</div>
                                        <div class="text-xs text-gray-500">{{ collect($note->sectionTargets())->map(fn ($key) => \App\Support\AcademyOptions::sectionLabel($key))->implode(', ') }}</div>
                                    </td>
                                    <td class="px-4 py-2">{{ $note->created_at?->format('d M Y') }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('teacher.notes.file', $note) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">
                                            {{ $note->original_name }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">No notes found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $notes->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
