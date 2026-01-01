<div class="space-y-6">
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
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->section }}, {{ $student->year }})</option>
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
                                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->section ?? 'N/A' }}, {{ $student->academic_year ?? $defaultYear }})</option>
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
                    <x-input-label value="Year" />
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
                    <x-input-label value="Year" />
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

    @php
        $selectedStudent = $marksStudents->firstWhere('id', $marksForm['student_id']);
        $previewTotal = 0;
        if ($markType === 'full') {
            $previewTotal = ($marksForm['mcq_mark'] ?? 0) + ($marksForm['cq_mark'] ?? 0) + ($marksForm['practical_mark'] ?? 0);
        } elseif ($markType === 'mcq') {
            $previewTotal = $marksForm['mcq_mark'] ?? 0;
        } else {
            $previewTotal = $marksForm['cq_mark'] ?? 0;
        }
    @endphp

    <div class="bg-white shadow rounded-lg p-4 space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Marks Input</h3>
            <p class="text-xs text-gray-500">Year defaults to {{ $defaultYear }} for entry and viewing.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Model Test" />
                <select wire:model="marksForm.model_test_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select test</option>
                    @foreach ($tests as $test)
                        <option value="{{ $test->id }}">{{ $test->name }} ({{ ucfirst($test->type) }}, {{ $test->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('marksForm.model_test_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="marksSectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">All Sections</option>
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
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->section }}, {{ $student->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('marksForm.student_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Subject" />
                <select wire:model="marksForm.subject" class="mt-1 block w-full rounded-md border-gray-300" wire:key="subject-select-{{ $marksSection ?? 'none' }}">
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
                <x-input-label value="Year" />
                <x-text-input type="number" wire:model.defer="marksForm.year" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('marksForm.year')" class="mt-1" />
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-3">
            @if ($markType !== 'cq')
                <div>
                    <x-input-label value="MCQ Marks (max {{ $maxMarks['mcq'] }})" />
                    <div class="flex gap-2">
                        <x-text-input type="number" step="0.01" wire:model.defer="marksForm.mcq_mark" class="mt-1 block w-full" />
                        <x-text-input type="number" step="0.01" wire:model.defer="marksForm.mcq_max" class="mt-1 block w-24" title="Max MCQ" />
                    </div>
                    <x-input-error :messages="$errors->get('marksForm.mcq_mark')" class="mt-1" />
                    <x-input-error :messages="$errors->get('marksForm.mcq_max')" class="mt-1" />
                </div>
            @endif

            @if ($markType !== 'mcq')
                <div>
                    <x-input-label value="CQ Marks (max {{ $maxMarks['cq'] }})" />
                    <div class="flex gap-2">
                        <x-text-input type="number" step="0.01" wire:model.defer="marksForm.cq_mark" class="mt-1 block w-full" />
                        <x-text-input type="number" step="0.01" wire:model.defer="marksForm.cq_max" class="mt-1 block w-24" title="Max CQ" />
                    </div>
                    <x-input-error :messages="$errors->get('marksForm.cq_mark')" class="mt-1" />
                    <x-input-error :messages="$errors->get('marksForm.cq_max')" class="mt-1" />
                </div>
            @endif

            @if ($markType === 'full' && ($maxMarks['practical'] ?? 0) > 0 || ($marksSection === 'science' && $markType === 'full'))
                <div>
                    <x-input-label value="Practical Marks (max {{ $maxMarks['practical'] }})" />
                    <div class="flex gap-2">
                        <x-text-input type="number" step="0.01" wire:model.defer="marksForm.practical_mark" class="mt-1 block w-full" />
                        <x-text-input type="number" step="0.01" wire:model.defer="marksForm.practical_max" class="mt-1 block w-24" title="Max Practical" />
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
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Total is calculated automatically.</div>
                <div class="text-base font-semibold text-indigo-700">Current total: {{ number_format($previewTotal, 2) }}</div>
            </div>
        </div>

        <div class="text-right">
            <x-primary-button type="button" wire:click="saveMarks">
                Save Marks
            </x-primary-button>
        </div>
    </div>
</div>
