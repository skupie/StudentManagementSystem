<?php

namespace App\Livewire\Students;

use App\Models\FeeInvoice;
use App\Models\Student;
use App\Support\AcademyOptions;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class StudentManager extends Component
{
    use WithPagination;

    #[Validate('nullable|string|max:255')]
    public string $search = '';

    #[Validate('required|in:all,active,inactive')]
    public string $statusFilter = 'active';
    #[Validate('required')]
    public string $classFilter = 'all';
    #[Validate('required')]
    public string $sectionFilter = 'all';

    #[Validate('array')]
    public array $form = [
        'name' => '',
        'gender' => '',
        'phone_number' => '',
        'class_level' => 'hsc_1',
        'academic_year' => '',
        'section' => 'science',
        'monthly_fee' => '',
        'full_payment_override' => false,
        'enrollment_date' => '',
        'status' => 'active',
        'notes' => '',
    ];

    public ?int $editingId = null;
    public ?int $attendanceStudentId = null;
    public array $attendanceRecords = [];
    public array $attendanceSummary = ['present' => 0, 'absent' => 0];
    public string $attendanceMonthFilter = '';

    public ?int $noteViewerId = null;
    public string $noteViewerName = '';
    public string $noteViewerBody = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'active'],
    ];

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.gender' => ['nullable', 'in:male,female,other'],
            'form.phone_number' => ['required', 'string', 'max:25', 'unique:students,phone_number,' . $this->editingId],
            'form.class_level' => ['required', 'in:' . implode(',', array_keys(AcademyOptions::classes()))],
            'form.academic_year' => ['required', 'string', 'max:25'],
            'form.section' => ['required', 'in:' . implode(',', array_keys(AcademyOptions::sections()))],
            'form.monthly_fee' => ['required', 'numeric', 'min:0'],
            'form.full_payment_override' => ['boolean'],
            'form.enrollment_date' => ['required', 'date'],
            'form.status' => ['required', 'in:active,inactive'],
            'form.notes' => ['nullable', 'string'],
        ];
    }

    public function render()
    {
        $filteredQuery = Student::query()
            ->when($this->search, fn ($query) => $query->where(function ($sub) {
                $sub->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $this->search . '%');
            }))
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->classFilter !== 'all', function ($query) {
                $query->where('class_level', $this->classFilter);
            })
            ->when($this->sectionFilter !== 'all', function ($query) {
                $query->where('section', $this->sectionFilter);
            })
            ->when($this->classFilter !== 'all', function ($query) {
                $query->where('class_level', $this->classFilter);
            })
            ->when($this->sectionFilter !== 'all', function ($query) {
                $query->where('section', $this->sectionFilter);
            });

        $students = (clone $filteredQuery)
            ->orderBy('name')
            ->paginate(10);

        $students->getCollection()->transform(function (Student $student) {
            $student->ensureInvoicesThroughMonth(now());
            $summary = $student->dueSummary();
            $student->invoice_total_due = $summary['amount'];
            $student->invoice_total_paid = $student->feeInvoices()->sum('amount_paid');
            $student->outstanding = $summary['amount'];
            $student->due_months = implode(', ', $summary['months']);
            return $student;
        });

        $filteredTotal = (clone $filteredQuery)->count();

        $classOptions = AcademyOptions::classes();
        $sectionOptions = AcademyOptions::sections();

        return view('livewire.students.student-manager', [
            'students' => $students,
            'classOptions' => $classOptions,
            'sectionOptions' => $sectionOptions,
            'filterClassOptions' => ['all' => 'All Classes'] + $classOptions,
            'filterSectionOptions' => ['all' => 'All Sections'] + $sectionOptions,
            'totalStudents' => $filteredTotal,
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $payload = $this->form;
        $payload['created_by'] = auth()->id();

        $student = Student::updateOrCreate(
            ['id' => $this->editingId],
            $payload
        );

        if (! $this->editingId) {
            $this->ensureInvoiceForCurrentMonth($student);
        }

        $this->dispatch('notify', message: 'Student saved successfully.');

        $this->resetForm();
    }

    public function edit(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $this->editingId = $student->id;
        $this->form = [
            'name' => $student->name,
            'gender' => $student->gender,
            'phone_number' => $student->phone_number,
            'class_level' => $student->class_level,
            'academic_year' => $student->academic_year,
            'section' => $student->section,
            'monthly_fee' => $student->monthly_fee,
            'full_payment_override' => (bool) $student->full_payment_override,
            'enrollment_date' => optional($student->enrollment_date)->format('Y-m-d'),
            'status' => $student->status,
            'notes' => $student->notes,
        ];
    }

    public function delete(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $student->delete();
        $this->dispatch('notify', message: 'Student removed.');
    }

    public function toggleStatus(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $student->status = $student->status === 'active' ? 'inactive' : 'active';
        $student->save();

        if ($student->status === 'active') {
            $this->ensureInvoiceForCurrentMonth($student);
        }

        $this->dispatch('notify', message: 'Student status updated.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'gender' => '',
            'phone_number' => '',
            'class_level' => 'hsc_1',
            'academic_year' => '',
            'section' => 'science',
            'monthly_fee' => '',
            'full_payment_override' => false,
            'enrollment_date' => '',
            'status' => 'active',
            'notes' => '',
        ];
    }

    public function showAttendanceHistory(int $studentId): void
    {
        $this->attendanceStudentId = $studentId;
        $this->attendanceMonthFilter = now()->format('Y-m');
        $this->loadAttendanceRecords();
    }

    public function closeAttendanceHistory(): void
    {
        $this->attendanceStudentId = null;
        $this->attendanceRecords = [];
        $this->attendanceMonthFilter = '';
        $this->attendanceSummary = ['present' => 0, 'absent' => 0];
    }

    public function showProfileNote(int $studentId): void
    {
        $student = Student::find($studentId);
        if (! $student) {
            return;
        }

        $this->noteViewerId = $student->id;
        $this->noteViewerName = $student->name;
        $this->noteViewerBody = trim((string) $student->notes) ?: 'No profile note added.';
    }

    public function closeProfileNote(): void
    {
        $this->noteViewerId = null;
        $this->noteViewerName = '';
        $this->noteViewerBody = '';
    }

    public function updatedAttendanceMonthFilter(): void
    {
        $this->loadAttendanceRecords();
    }

    protected function loadAttendanceRecords(): void
    {
        if (! $this->attendanceStudentId) {
            return;
        }

        $student = Student::find($this->attendanceStudentId);
        if (! $student) {
            $this->closeAttendanceHistory();
            return;
        }

        $query = $student->attendances()->orderByDesc('attendance_date');

        if ($this->attendanceMonthFilter) {
            $query->whereBetween('attendance_date', [
                \Carbon\Carbon::parse($this->attendanceMonthFilter)->startOfMonth(),
                \Carbon\Carbon::parse($this->attendanceMonthFilter)->endOfMonth(),
            ]);
        }

        $this->attendanceRecords = $query
            ->limit(60)
            ->get()
            ->map(function ($record) {
                return [
                    'date' => optional($record->attendance_date)->format('d M Y'),
                    'status' => $record->status,
                    'category' => $record->category,
                    'note' => $record->note,
                ];
            })
            ->toArray();

        $month = $this->attendanceMonthFilter
            ? Carbon::parse($this->attendanceMonthFilter)->startOfMonth()
            : now()->startOfMonth();

        $presentCount = $student->attendances()
            ->where('status', 'present')
            ->whereBetween('attendance_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->count();

        $absentCount = $student->attendances()
            ->where('status', 'absent')
            ->whereBetween('attendance_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->count();

        $this->attendanceSummary = [
            'present' => $presentCount,
            'absent' => $absentCount,
        ];
    }
    protected function ensureInvoiceForCurrentMonth(Student $student): void
    {
        $month = now()->startOfMonth();

        FeeInvoice::firstOrCreate(
            [
                'student_id' => $student->id,
                'billing_month' => $month,
            ],
            [
                'due_date' => $month->copy()->endOfMonth(),
                'amount_due' => $student->monthly_fee,
                'amount_paid' => 0,
                'status' => 'pending',
            ]
        );
    }
}
