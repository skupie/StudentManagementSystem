<div class="space-y-6">
    @if (! $isTeacherRole)
        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Model Test Student</h3>
                <p class="text-xs text-gray-500">Stores separately from regular students.</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-3">
                <div class="flex-1">
                    <x-input-label value="Load existing student" />
                    <select wire:model="selectedStudentId" wire:change="loadStudent($event.target.value)" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Select</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->section }}, Batch {{ $student->year }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <x-input-label value="Use student from main list" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div>
                            <x-input-label value="Class" />
                            <select wire:model.live="existingClass" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All</option>
                                @foreach ($classOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Section" />
                            <select wire:model.live="existingSection" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All</option>
                                @foreach ($sectionOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <div class="flex gap-2">
                                <select wire:model.live="existingStudentId" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Select student</option>
                                    @foreach ($existingStudents as $student)
                                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->section ?? 'N/A' }}, Batch {{ $student->academic_year ?? $defaultYear }})</option>
                                    @endforeach
                                </select>
                                <x-secondary-button type="button" class="mt-1" wire:click="useExistingStudent">
                                    Load
                                </x-secondary-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <x-input-label value="Student Name" />
                    <x-text-input type="text" wire:model.defer="studentForm.name" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('studentForm.name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Contact Number" />
                    <x-text-input type="text" wire:model.defer="studentForm.contact_number" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('studentForm.contact_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Section" />
                    <select wire:model.defer="studentForm.section" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($sectionOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('studentForm.section')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="HSC Batch" />
                    <x-text-input type="number" wire:model.defer="studentForm.year" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('studentForm.year')" class="mt-1" />
                </div>
            </div>

            <div class="text-right">
                <x-primary-button type="button" wire:click="createStudent">
                    Save Student
                </x-primary-button>
            </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Model Test</h3>
                <p class="text-xs text-gray-500">Name + type + subject per year.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <x-input-label value="Model Test Name" />
                    <x-text-input type="text" wire:model.defer="testForm.name" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('testForm.name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Model Test Type" />
                    <select wire:model.defer="testForm.type" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="full">Full Model Test</option>
                        <option value="mcq">MCQ Model Test</option>
                        <option value="cq">CQ Model Test</option>
                    </select>
                    <x-input-error :messages="$errors->get('testForm.type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="HSC Batch" />
                    <x-text-input type="number" wire:model.defer="testForm.year" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('testForm.year')" class="mt-1" />
                </div>
            </div>

            <div class="text-right">
                <x-primary-button type="button" wire:click="createModelTest">
                    Save Model Test
                </x-primary-button>
            </div>
            </div>
        </div>
    @endif

    @php
        $selectedStudent = $marksStudents->firstWhere('id', $marksForm['student_id']);
    @endphp

    <div
        x-data="{
            subject: @entangle('marksForm.subject').live,
            section: @entangle('marksSectionFilter').live,
            practicalMark: @entangle('marksForm.practical_mark'),
            practicalMax: @entangle('marksForm.practical_max'),
            mcq: @entangle('marksForm.mcq_mark').live,
            cq: @entangle('marksForm.cq_mark').live,
            type: '{{ $markType }}',
            showPractical: false,
            recompute() {
                const normalize = (v) => (v || '').toString().toLowerCase().replace(/_(1st|2nd)$/,'').trim();
                const allowed = ['physics','chemistry','math','botany','zoology','ict'];
                const sec = (this.section || '').toLowerCase();
                const subj = normalize(this.subject);
                this.showPractical = (sec === 'science' && allowed.includes(subj)) || subj === 'ict';
                if (!this.showPractical) {
                    this.practicalMark = null;
                    this.practicalMax = 0;
                } else if (!this.practicalMax || this.practicalMax === 0) {
                    this.practicalMax = 25;
                }
            },
            currentTotal() {
                const toNum = (v) => parseFloat(v) || 0;
                const mcqVal = this.type === 'mcq' || this.type === 'full' ? toNum(this.mcq) : 0;
                const cqVal = this.type === 'cq' || this.type === 'full' ? toNum(this.cq) : 0;
                const pracVal = this.type === 'full' && this.showPractical ? toNum(this.practicalMark) : 0;
                return (mcqVal + cqVal + pracVal).toFixed(2);
            },
        }"
        x-init="recompute()"
        x-effect="recompute()"
        class="bg-white shadow rounded-lg p-4 space-y-3"
    >
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Marks Input</h3>
            <p class="text-xs text-gray-500">HSC Batch defaults to {{ $defaultYear }} for entry and viewing.</p>
        </div>
        @if ($isTeacherRole && ! $teacherHasAllowedSubjects)
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                No subject is assigned to your teacher profile. Contact admin to assign subject before entering marks.
            </div>
        @endif

        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Model Test" />
                <select wire:model.live="marksForm.model_test_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select test</option>
                    @foreach ($tests as $test)
                        <option value="{{ $test->id }}">{{ $test->name }} ({{ ucfirst($test->type) }}, Batch {{ $test->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('marksForm.model_test_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="marksSectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @if (! $isTeacherRole)
                        <option value="">All Sections</option>
                    @endif
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Student" />
                <select wire:model="marksForm.student_id" class="mt-1 block w-full rounded-md border-gray-300" wire:key="marks-student-select-{{ $marksSection ?? 'all' }}">
                    <option value="">Select student</option>
                    @foreach ($marksStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->section }}, Batch {{ $student->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('marksForm.student_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Subject" />
                <select wire:model="marksForm.subject" class="mt-1 block w-full rounded-md border-gray-300" wire:key="subject-select-{{ $marksSection ?? 'none' }}-{{ $editingResultId ?? 'new' }}">
                    @foreach ($subjectOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Subjects are filtered by the selected student's section ({{ $marksSection ?? 'N/A' }}).</p>
                <x-input-error :messages="$errors->get('marksForm.subject')" class="mt-1" />
            </div>
            <div class="flex items-center gap-2 mt-6">
                <input type="checkbox" wire:model.defer="marksForm.optional_subject" id="optional_subject" class="rounded border-gray-300 text-indigo-600">
                <label for="optional_subject" class="text-sm text-gray-700">4th subject (optional)</label>
            </div>
            <div>
                <x-input-label value="HSC Batch" />
                <x-text-input type="number" wire:model.defer="marksForm.year" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('marksForm.year')" class="mt-1" />
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-3">
            @if ($markType !== 'cq')
                <div wire:key="mcq-input-{{ $editingResultId ?? 'new' }}">
                    <x-input-label value="MCQ Marks (max {{ $maxMarks['mcq'] }})" />
                    <div class="flex gap-2">
                        <x-text-input type="number" step="0.01" wire:model.live="marksForm.mcq_mark" class="mt-1 block w-full" />
                        <x-text-input type="number" step="0.01" wire:model.live="marksForm.mcq_max" class="mt-1 block w-24" title="Max MCQ" />
                    </div>
                    <x-input-error :messages="$errors->get('marksForm.mcq_mark')" class="mt-1" />
                    <x-input-error :messages="$errors->get('marksForm.mcq_max')" class="mt-1" />
                </div>
            @endif

            @if ($markType !== 'mcq')
                <div wire:key="cq-input-{{ $editingResultId ?? 'new' }}">
                    <x-input-label value="CQ Marks (max {{ $maxMarks['cq'] }})" />
                    <div class="flex gap-2">
                        <x-text-input type="number" step="0.01" wire:model.live="marksForm.cq_mark" class="mt-1 block w-full" />
                        <x-text-input type="number" step="0.01" wire:model.live="marksForm.cq_max" class="mt-1 block w-24" title="Max CQ" />
                    </div>
                    <x-input-error :messages="$errors->get('marksForm.cq_mark')" class="mt-1" />
                    <x-input-error :messages="$errors->get('marksForm.cq_max')" class="mt-1" />
                </div>
            @endif

            @if ($markType === 'full')
                <div x-show="showPractical" x-cloak wire:key="practical-input-{{ $editingResultId ?? 'new' }}">
                    <x-input-label value="Practical Marks (max {{ $maxMarks['practical'] }})" />
                    <div class="flex gap-2">
                        <x-text-input type="number" step="0.01" wire:model.live="marksForm.practical_mark" class="mt-1 block w-full" x-bind:disabled="!showPractical" />
                        <x-text-input type="number" step="0.01" wire:model.live="marksForm.practical_max" class="mt-1 block w-24" title="Max Practical" x-bind:disabled="!showPractical" />
                    </div>
                    <x-input-error :messages="$errors->get('marksForm.practical_mark')" class="mt-1" />
                    <x-input-error :messages="$errors->get('marksForm.practical_max')" class="mt-1" />
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between text-sm text-gray-600">
            <div>
                <div>Model Test Type: <span class="font-semibold">{{ ucfirst($markType) }}</span></div>
                <div>Section: <span class="font-semibold">{{ $selectedStudent->section ?? 'Select a student' }}</span></div>
                    <div>Default Max: MCQ {{ $maxMarks['mcq'] }}, CQ {{ $maxMarks['cq'] }} @if ($maxMarks['practical'] > 0), Practical {{ $maxMarks['practical'] }} @endif</div>
                    <div class="text-xs text-gray-500">Current Max Values — MCQ: {{ $marksForm['mcq_max'] ?? '–' }}, CQ: {{ $marksForm['cq_max'] ?? '–' }}, Practical: {{ $marksForm['practical_max'] ?? '–' }}</div>
                </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Total is calculated automatically.</div>
                <div class="text-base font-semibold text-indigo-700">Current total: <span x-text="currentTotal()"></span></div>
            </div>
        </div>

        <div class="text-right">
            @if ($isTeacherRole && ! $teacherHasAllowedSubjects)
                <x-primary-button type="button" disabled>
                    Save Marks
                </x-primary-button>
            @else
                <x-primary-button type="button" wire:click="saveMarks">
                    Save Marks
                </x-primary-button>
            @endif
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="font-semibold text-gray-800">Recorded Marks</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-3 py-2">Student</th>
                        <th class="px-3 py-2">Model Test</th>
                        <th class="px-3 py-2">Subject</th>
                        <th class="px-3 py-2">HSC Batch</th>
                        <th class="px-3 py-2">Marks</th>
                        <th class="px-3 py-2">Grade</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($results as $result)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-900">{{ $result->student?->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $result->student?->section }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-900">{{ $result->test?->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($result->test?->type ?? '') }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $subjectOptions[$result->subject] ?? ($result->subject ?? '—') }}</td>
                            <td class="px-3 py-2">{{ $result->year }}</td>
                            <td class="px-3 py-2 text-xs text-gray-700">
                                @if ($result->mcq_mark !== null)
                                    <div>MCQ: {{ number_format($result->mcq_mark, 2) }}</div>
                                @endif
                                @if ($result->cq_mark !== null)
                                    <div>CQ: {{ number_format($result->cq_mark, 2) }}</div>
                                @endif
                                @if ($result->practical_mark !== null)
                                    <div>Practical: {{ number_format($result->practical_mark, 2) }}</div>
                                @endif
                                <div class="font-semibold text-gray-900">Total: {{ number_format($result->total_mark ?? 0, 2) }}</div>
                                @if ($result->optional_subject)
                                    <div class="text-xs text-indigo-600 font-semibold">Optional</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $result->grade === 'F' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $result->grade ?? '—' }}
                                </span>
                                <div class="text-xs text-gray-600">GP: {{ $result->grade_point ?? '—' }}</div>
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <x-secondary-button type="button" wire:click="editResult({{ $result->id }})" class="text-xs">
                                    Edit
                                </x-secondary-button>
                                <x-danger-button type="button" wire:click="promptDelete({{ $result->id }})" class="text-xs">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                No marks recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $results->links() }}
    </div>

    @if (! is_null($confirmingDeleteId ?? null))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Deletion</h3>
                    <button wire:click="cancelDelete" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Do you want to delete {{ $confirmingDeleteName }}'s model test mark? This cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="deleteConfirmed">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
