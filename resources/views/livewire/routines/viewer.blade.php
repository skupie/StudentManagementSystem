<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6 space-y-1 text-center">
        <h1 class="text-2xl font-bold text-gray-800">Basic Academy</h1>
        <p class="text-sm text-gray-600">Class Routine — {{ \Carbon\Carbon::parse($viewDate)->format('d M Y') }}</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        @foreach ($tables as $key => $table)
            <div class="bg-white shadow rounded-lg p-4 space-y-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">{{ $table['class_label'] }} — {{ $table['section_label'] }}</h3>
                    <p class="text-xs text-gray-500">Table: {{ strtoupper(str_replace('|', '_', $key)) }}</p>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-gray-500">No entries for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
