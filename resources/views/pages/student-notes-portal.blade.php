<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notes') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (! $student)
                <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
                    No student profile is linked with your account. Contact admin to link your login credentials.
                </div>
            @else
                <div class="bg-white shadow rounded-lg p-4 space-y-4">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($subjectOptions as $key => $label)
                            <a href="{{ route('student.notes', ['subject' => $key]) }}"
                               class="px-3 py-1.5 rounded-full text-sm border {{ $selectedSubject === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                    @if (! $subjectColumnExists)
                        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                            Subject-based note grouping will work after running migrations on this server.
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow rounded-lg p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <th class="px-4 py-2">Title</th>
                                    <th class="px-4 py-2">Subject</th>
                                    <th class="px-4 py-2">Shared By</th>
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
                                        <td class="px-4 py-2">{{ $note->display_subject ?? ($note->subject ? \App\Support\AcademyOptions::subjectLabel($note->subject) : '') }}</td>
                                        <td class="px-4 py-2">{{ $note->uploader?->name ?? 'Unknown' }}</td>
                                        <td class="px-4 py-2">{{ $note->created_at?->format('d M Y') }}</td>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('teacher.notes.file', ['teacherNote' => $note->id, 'v' => $note->updated_at?->timestamp]) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">
                                                {{ $note->original_name }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No notes found for this subject.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (method_exists($notes, 'links'))
                        <div class="mt-4">{{ $notes->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
