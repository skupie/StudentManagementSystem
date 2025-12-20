<div class="bg-white shadow rounded-lg p-6 space-y-6">
    <div class="grid md:grid-cols-4 gap-4">
        <div>
            <x-input-label value="Search by name or phone" />
            <x-text-input type="text" class="mt-1 block w-full" placeholder="e.g. Rahim or 01..." wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label value="Status Filter" />
            <select wire:model.live="statusFilter" class="mt-1 block w-full rounded-md border-gray-300">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="passed">Passed</option>
                <option value="all">All (excluding passed)</option>
            </select>
        </div>
        <div>
            <x-input-label value="Class Filter" />
            <select wire:model.live.debounce.300ms="classFilter" class="mt-1 block w-full rounded-md border-gray-300">
                @foreach ($filterClassOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Section Filter" />
            <select wire:model.live.debounce.300ms="sectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                @foreach ($filterSectionOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-secondary-button wire:click="resetForm">
            {{ __('Reset Form') }}
        </x-secondary-button>
        <x-secondary-button type="button" wire:click="exportCsv">
            {{ __('Export CSV') }}
        </x-secondary-button>
        <x-secondary-button type="button" onclick="window.location='{{ route('students.export.excel') }}'">
            {{ __('Download Excel') }}
        </x-secondary-button>
    </div>

    <form wire:submit.prevent="importCsv" class="bg-white shadow rounded-lg p-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h4 class="font-semibold text-gray-800">CSV Import / Export</h4>
                <p class="text-sm text-gray-500">Import or export students with attendance logs (semicolon-separated) in one file.</p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 md:items-end w-full md:w-auto">
                <div>
                    <x-input-label value="Import CSV" />
                    <input type="file" wire:model="importFile" accept=".csv,text/csv" class="mt-1 block w-full text-sm">
                    <x-input-error :messages="$errors->get('importFile')" class="mt-1" />
                </div>
                <div class="flex gap-2">
                    <x-secondary-button type="button" wire:click="exportCsv">
                        Export CSV
                    </x-secondary-button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="importFile,importCsv">
                        Import CSV
                    </x-primary-button>
                </div>
            </div>
        </div>
    </form>

    <div class="grid md:grid-cols-3 gap-4">
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p class="text-sm text-blue-800">Total Students</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ number_format($totalStudents) }}</p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="grid md:grid-cols-3 gap-4">
        <div>
            <x-input-label for="name" value="Full Name" />
            <x-text-input id="name" wire:model.defer="form.name" type="text" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('form.name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="gender" value="Gender" />
            <select id="gender" wire:model.defer="form.gender" class="mt-1 block w-full rounded-md border-gray-300">
                <option value="">Select</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
            <x-input-error :messages="$errors->get('form.gender')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="phone" value="Phone Number" />
            <x-text-input id="phone" wire:model.defer="form.phone_number" type="text" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('form.phone_number')" class="mt-1" />
        </div>

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
            <x-input-label value="Academic Year" />
            <x-text-input type="text" wire:model.defer="form.academic_year" class="mt-1 block w-full" placeholder="2024-2025" />
            <x-input-error :messages="$errors->get('form.academic_year')" class="mt-1" />
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
            <x-input-label value="Monthly Payment (৳)" />
            <x-text-input type="number" step="0.01" wire:model.defer="form.monthly_fee" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('form.monthly_fee')" class="mt-1" />
        </div>
        <div>
            <x-input-label value="Enrollment Date" />
            <x-text-input type="date" wire:model.defer="form.enrollment_date" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('form.enrollment_date')" class="mt-1" />
        </div>
        <div>
            <x-input-label value="Status" />
            <select wire:model.defer="form.status" class="mt-1 block w-full rounded-md border-gray-300">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <x-input-error :messages="$errors->get('form.status')" class="mt-1" />
        </div>
        <div>
            <x-input-label value="Always Charge Full" />
            <div class="flex items-center mt-2">
                <input type="checkbox" wire:model.defer="form.full_payment_override" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                <span class="ml-2 text-sm text-gray-600">Ignore attendance rules; always bill full month</span>
            </div>
            <x-input-error :messages="$errors->get('form.full_payment_override')" class="mt-1" />
        </div>
        <div>
            <x-input-label value="Passed (exclude from active/inactive)" />
            <div class="flex items-center mt-2">
                <input type="checkbox" wire:model.defer="form.is_passed" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                <span class="ml-2 text-sm text-gray-600">Mark as passed</span>
            </div>
            <x-input-error :messages="$errors->get('form.is_passed')" class="mt-1" />
        </div>

        <div class="md:col-span-3">
            <x-input-label value="Notes" />
            <textarea wire:model.defer="form.notes" class="mt-1 block w-full rounded-md border-gray-300" rows="2"></textarea>
            <x-input-error :messages="$errors->get('form.notes')" class="mt-1" />
        </div>

        <div class="md:col-span-3 flex justify-end gap-3">
            @if ($editingId)
                <x-secondary-button wire:click="resetForm" type="button">
                    {{ __('Cancel Editing') }}
                </x-secondary-button>
            @endif
            <x-primary-button type="submit">
                {{ $editingId ? __('Update Student') : __('Save Student') }}
            </x-primary-button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-2">Student</th>
                    <th class="px-4 py-2">Class / Section</th>
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2">Monthly Fee</th>
                    <th class="px-4 py-2">Outstanding</th>
                    @if ($statusFilter === 'passed')
                        <th class="px-4 py-2">Passing Year</th>
                    @endif
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    @php($outstanding = max(0, $student->outstanding ?? 0))
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <button type="button" class="font-semibold text-indigo-600 hover:underline" wire:click="showProfileNote({{ $student->id }})">
                                {{ $student->name }}
                            </button>
                            <div class="text-gray-500 text-xs">Enrolled {{ optional($student->enrollment_date)->format('d M Y') }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <div>{{ $classOptions[$student->class_level] ?? $student->class_level }}</div>
                            <div class="text-xs text-gray-500">{{ $sectionOptions[$student->section] ?? $student->section }}</div>
                        </td>
                        <td class="px-4 py-2">{{ $student->phone_number }}</td>
                        <td class="px-4 py-2">৳ {{ number_format($student->monthly_fee, 2) }}</td>
                        <td class="px-4 py-2 {{ $outstanding > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                            ৳ {{ number_format($outstanding, 2) }}
                        </td>
                        @if ($statusFilter === 'passed')
                            <td class="px-4 py-2">
                                {{ $student->passed_year ?? '-' }}
                            </td>
                        @endif
                        <td class="px-4 py-2 space-y-1 text-center">
                            <span class="px-3 py-1 rounded-full text-xs {{ $student->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                            @if($student->status === 'inactive' && $student->inactive_at)
                                <div class="text-xs text-red-600 font-semibold">
                                    {{ \Carbon\Carbon::parse($student->inactive_at)->format('d M Y') }}
                                </div>
                            @endif
                            @if($student->is_passed)
                                <span class="px-2 py-1 rounded-full text-xs bg-indigo-100 text-indigo-800">Passed</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="tel:{{ $student->phone_number }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-50">
                                {{ __('Call') }}
                            </a>
                            <x-secondary-button wire:click="edit({{ $student->id }})" type="button" class="text-xs">
                                {{ __('Edit') }}
                            </x-secondary-button>
                            @if (! $student->is_passed)
                                @if ($student->status === 'active')
                                    <x-secondary-button wire:click="promptDeactivate({{ $student->id }})" type="button" class="text-xs">
                                        {{ __('Deactivate') }}
                                    </x-secondary-button>
                                @else
                                    <x-secondary-button wire:click="toggleStatus({{ $student->id }})" type="button" class="text-xs">
                                        {{ __('Activate') }}
                                    </x-secondary-button>
                                @endif
                                <x-secondary-button wire:click="promptMarkPassed({{ $student->id }})" type="button" class="text-xs">
                                    {{ __('Mark Passed') }}
                                </x-secondary-button>
                            @else
                                <x-secondary-button wire:click="promptUnmarkPassed({{ $student->id }})" type="button" class="text-xs">
                                    {{ __('Unmark Passed') }}
                                </x-secondary-button>
                            @endif
                            <x-secondary-button wire:click="showAttendanceHistory({{ $student->id }})" type="button" class="text-xs">
                                {{ __('Attendance Log') }}
                            </x-secondary-button>
                            <x-danger-button
                                type="button"
                                class="text-xs"
                                wire:click="promptDelete({{ $student->id }})"
                            >
                                {{ __('Delete') }}
                            </x-danger-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                            No students found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>
        {{ $students->links() }}
    </div>

    @if ($attendanceStudentId)
        <div class="fixed inset-0 z-[1000] bg-black bg-opacity-50 flex items-start justify-center overflow-y-auto p-4">
            <div class="bg-white rounded-lg shadow-xl md:w-[48%] max-w-4xl mt-10 h-[40vh] p-6 overflow-y-auto">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
                    <h3 class="text-lg font-semibold text-gray-800">Attendance History</h3>
                    <div class="flex items-center gap-4 text-sm">
                        <div class="text-green-600 font-semibold">Present: {{ $attendanceSummary['present'] }}</div>
                        <div class="text-red-600 font-semibold">Absent: {{ $attendanceSummary['absent'] }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-input-label value="Month" />
                        <x-text-input type="month" wire:model.live="attendanceMonthFilter" class="mt-1 block w-full" />
                    </div>
                    <button wire:click="closeAttendanceHistory" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500 border-b">
                                <th class="py-2">Date</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Category</th>
                                <th class="py-2">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendanceRecords as $record)
                                <tr class="border-b">
                                    <td class="py-2">{{ $record['date'] }}</td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded-full text-xs {{ $record['status'] === 'present' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ ucfirst($record['status']) }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-xs text-gray-500">{{ $record['category'] }}</td>
                                    <td class="py-2 text-xs text-gray-600">{{ $record['note'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-500">No attendance records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-4">
                    <x-secondary-button type="button" wire:click="closeAttendanceHistory">Close</x-secondary-button>
                </div>
            </div>
        </div>
    @endif

    @if ($noteViewerId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Profile Note — {{ $noteViewerName }}</h3>
                    <button wire:click="closeProfileNote" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="text-sm text-gray-700 whitespace-pre-line border rounded-md p-4 bg-gray-50">
                    {{ $noteViewerBody }}
                </div>
                <div class="text-right">
                    <x-secondary-button type="button" wire:click="closeProfileNote">
                        Close
                    </x-secondary-button>
                </div>
            </div>
        </div>
    @endif

    @if (! is_null($confirmingDeleteId ?? null))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Deletion</h3>
                    <button wire:click="cancelDelete" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Do You want to delete <span class="font-semibold">{{ $confirmingDeleteName }}</span>'s Data? This cannot be undone
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="delete({{ $confirmingDeleteId }})">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif

    @if (! is_null($confirmingDeactivateId ?? null))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Deactivation</h3>
                    <button wire:click="cancelDeactivate" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Do You want to deactivate <span class="font-semibold">{{ $confirmingDeactivateName }}</span>? They will be marked inactive
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDeactivate">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="confirmDeactivate">Deactivate</x-danger-button>
                </div>
            </div>
        </div>
    @endif

    @if (! is_null($confirmingPassId ?? null))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $confirmingPassModeMark ? 'Confirm Mark Passed' : 'Confirm Unmark Passed' }}</h3>
                    <button wire:click="cancelPassConfirm" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    {{ $confirmingPassModeMark ? 'Mark' : 'Remove' }} <span class="font-semibold">{{ $confirmingPassName }}</span> as passed?
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelPassConfirm">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="confirmPassAction">
                        {{ $confirmingPassModeMark ? 'Mark Passed' : 'Unmark Passed' }}
                    </x-danger-button>
                </div>
            </div>
        </div>
    @endif

</div>
