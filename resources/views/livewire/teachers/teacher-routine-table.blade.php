<div class="bg-white shadow rounded-lg p-4 space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">My Classes Today</h3>
            <p class="text-sm text-gray-500">
                {{ \Carbon\Carbon::parse($viewDate)->timezone('Asia/Dhaka')->format('d M Y') }} (BST)
            </p>
        </div>
        <div class="w-full md:w-56">
            <x-input-label value="Date" />
            <x-text-input type="date" wire:model.live="viewDate" class="mt-1 block w-full" />
        </div>
    </div>

    @if (! $teacher)
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            No linked teacher profile found for your account. Please contact admin.
        </div>
    @else
        <div class="text-sm text-gray-600">
            Teacher: <span class="font-semibold text-gray-800">{{ $teacher->name }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Time</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Class</th>
                        <th class="px-4 py-2">Section</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $row['time_slot'] }}</td>
                            <td class="px-4 py-2">{{ $row['subject'] }}</td>
                            <td class="px-4 py-2">{{ $row['class_label'] }}</td>
                            <td class="px-4 py-2">{{ $row['section_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                You have no classes on this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

