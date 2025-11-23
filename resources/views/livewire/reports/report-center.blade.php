<div class="space-y-6">
    <div class="grid md:grid-cols-3 gap-4">
        <div class="p-4 bg-white shadow rounded-lg">
            <div class="text-sm text-gray-500">Students</div>
            <div class="text-3xl font-bold text-gray-800">{{ $totalStudents }}</div>
            <div class="text-xs text-green-600 mt-1">{{ $activeStudents }} active</div>
        </div>
        <div class="p-4 bg-white shadow rounded-lg">
            <div class="text-sm text-gray-500">Attendance Entries (This Month)</div>
            <div class="text-3xl font-bold text-blue-600">{{ $attendanceCount }}</div>
        </div>
        <div class="p-4 bg-white shadow rounded-lg">
            <div class="text-sm text-gray-500">Weekly Exam Records</div>
            <div class="text-3xl font-bold text-purple-600">{{ $weeklyMarks }}</div>
        </div>
    </div>

    @if (! $isAssistant)
        <div class="grid md:grid-cols-2 gap-4">
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="text-sm text-gray-600">Total Fees Collected</div>
                <div class="text-3xl font-bold text-green-700 mt-2">৳ {{ number_format($feeCollected, 2) }}</div>
            </div>
            <div class="p-4 bg-red-50 rounded-lg">
                <div class="text-sm text-gray-600">Outstanding Fees</div>
                <div class="text-3xl font-bold text-red-700 mt-2">৳ {{ number_format($outstandingFees, 2) }}</div>
            </div>
        </div>
    @endif

    <div class="grid gap-4 {{ $isAssistant ? '' : 'md:grid-cols-3' }}">
        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <h3 class="font-semibold text-gray-800">Weekly Exam PDF</h3>
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="examClass" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="examSection" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All Sections</option>
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Subject" />
                <select wire:model.live="examSubject" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($subjectOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Exam Month" />
                <x-text-input type="month" wire:model.live="examMonth" class="mt-1 block w-full" />
            </div>
            <div class="flex gap-2">
                <x-primary-button type="button" wire:click="downloadExamReport" class="w-full justify-center">
                    PDF
                </x-primary-button>
                <x-secondary-button type="button" wire:click="downloadExamExcel" class="w-full justify-center">
                    Excel
                </x-secondary-button>
            </div>
        </div>

        @if (! $isAssistant)
            <div class="bg-white shadow rounded-lg p-4 space-y-3">
                <h3 class="font-semibold text-gray-800">Due List PDF</h3>
                <div>
                    <x-input-label value="Class" />
                    <select wire:model.live="dueClass" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All Classes</option>
                        @foreach ($classOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Section" />
                    <select wire:model.live="dueSection" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All Sections</option>
                        @foreach ($sectionOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Year" />
                    <x-text-input type="text" wire:model.live="dueYear" class="mt-1 block w-full" placeholder="2024" />
                </div>
                <div class="flex gap-2">
                    <x-secondary-button type="button" wire:click="downloadDueReport" class="w-full justify-center">
                        PDF
                    </x-secondary-button>
                    <x-secondary-button type="button" wire:click="downloadDueExcel" class="w-full justify-center">
                        Excel
                    </x-secondary-button>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4 space-y-3">
                <h3 class="font-semibold text-gray-800">Finance PDF</h3>
                <div>
                    <x-input-label value="Start" />
                    <x-text-input type="date" wire:model.live="financeRangeStart" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label value="End" />
                    <x-text-input type="date" wire:model.live="financeRangeEnd" class="mt-1 block w-full" />
                </div>
                <div class="flex gap-2">
                    <x-secondary-button type="button" wire:click="downloadFinanceReport" class="w-full justify-center">
                        PDF
                    </x-secondary-button>
                    <x-secondary-button type="button" wire:click="downloadFinanceExcel" class="w-full justify-center">
                        Excel
                    </x-secondary-button>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-3">
        <h3 class="font-semibold text-gray-800">Attendance Matrix</h3>
        <div class="grid md:grid-cols-4 gap-3">
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="attendanceReportClass" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All Classes</option>
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="attendanceReportSection" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All Sections</option>
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Academic Year" />
                <x-text-input type="text" wire:model.live="attendanceReportYear" class="mt-1 block w-full" placeholder="2024-2025" />
            </div>
            <div>
                <x-input-label value="Month" />
                <x-text-input type="month" wire:model.live="attendanceReportMonth" class="mt-1 block w-full" />
            </div>
        </div>
        <div class="flex gap-2">
            <x-secondary-button type="button" wire:click="downloadAttendanceMatrix" class="justify-center bg-emerald-200 text-black hover:bg-emerald-300 border-emerald-300">
                Excel
            </x-secondary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-3">
        <h3 class="font-semibold text-gray-800">Individual Weekly Exam Report</h3>
        <div class="grid md:grid-cols-4 gap-3">
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="studentReportClass" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="studentReportSection" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Student" />
                <select wire:model.live="studentReportStudentId" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select student</option>
                    @foreach ($studentReportOptions as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->phone_number }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Month" />
                <x-text-input type="month" wire:model.live="studentReportMonth" class="mt-1 block w-full" />
            </div>
        </div>
        <div class="flex gap-2">
            <x-primary-button type="button" wire:click="downloadStudentExamReport" :disabled="!$studentReportStudentId" class="justify-center">
                PDF
            </x-primary-button>
            <x-secondary-button type="button" wire:click="downloadStudentExamExcel" :disabled="!$studentReportStudentId" class="justify-center">
                Excel
            </x-secondary-button>
        </div>
    </div>
</div>
