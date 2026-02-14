<div class="bg-white shadow rounded-lg p-4 space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Weekly Exam Update Status</h3>
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
            No linked teacher profile found for this account.
        </div>
    @elseif ($totalScheduled === 0)
        <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            No weekly exams assigned up to this date.
        </div>
    @elseif ($pendingRows->isEmpty())
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            Marks entry detected. Pending alert cleared ({{ $completedCount }}/{{ $totalScheduled }} assignments).
        </div>
    @else
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Weekly exam marks update pending. Enter marks to clear this alert.
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Exam Date</th>
                        <th class="px-4 py-2">Exam</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Class</th>
                        <th class="px-4 py-2">Section</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($pendingRows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($row['exam_date'])->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ $row['exam_name'] }}</td>
                            <td class="px-4 py-2">{{ $row['subject'] }}</td>
                            <td class="px-4 py-2">{{ $row['class_label'] }}</td>
                            <td class="px-4 py-2">{{ $row['section_label'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
