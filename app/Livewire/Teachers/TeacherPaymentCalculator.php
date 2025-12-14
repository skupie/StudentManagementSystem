<?php

namespace App\Livewire\Teachers;

use App\Models\Expense;
use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\AuditLog;
use Livewire\Component;
use Illuminate\Support\Facades\Schema;

class TeacherPaymentCalculator extends Component
{
    public array $classCounts = [];
    public string $expenseDate;
    public string $note = '';
    public bool $saved = false;
    public string $payoutMonth;

    public function mount(): void
    {
        $this->expenseDate = now()->format('Y-m-d');
        $this->payoutMonth = now()->format('Y-m');
    }

    public function render()
    {
        $monthDate = \Carbon\Carbon::parse($this->payoutMonth . '-01')->startOfMonth();
        $existing = TeacherPayment::whereDate('payout_month', $monthDate)->get()->keyBy('teacher_id');

        $teacherQuery = Teacher::query()->orderBy('name');
        if (Schema::hasColumn('teachers', 'is_active')) {
            $teacherQuery->where('is_active', true);
        }

        $teachers = $teacherQuery->get()
            ->map(function (Teacher $teacher) use ($existing) {
                $existingPayment = $existing[$teacher->id] ?? null;
                $inputCount = $this->classCounts[$teacher->id] ?? $existingPayment?->class_count ?? 0;
                $count = (int) $inputCount;
                $teacher->calculated_total = round($count * (float) ($teacher->payment ?? 0), 2);
                $teacher->input_count = $count;
                $teacher->existing_payment = $existingPayment;
                return $teacher;
            });

        $grandTotal = $teachers->sum('calculated_total');

        return view('livewire.teachers.teacher-payment-calculator', [
            'teachers' => $teachers,
            'grandTotal' => $grandTotal,
            'existingPayments' => $existing,
        ]);
    }

    public function save(): void
    {
        $monthDate = \Carbon\Carbon::parse($this->payoutMonth . '-01')->startOfMonth();

        $teachers = Teacher::whereIn('id', array_keys($this->classCounts))->get()->keyBy('id');
        $savedAny = false;

        foreach ($this->classCounts as $teacherId => $count) {
            $count = (int) $count;
            if ($count <= 0 || ! $teachers->has($teacherId)) {
                continue;
            }
            $teacher = $teachers[$teacherId];
            $amount = round($count * (float) ($teacher->payment ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $expense = Expense::create([
                'expense_date' => \Carbon\Carbon::parse($this->expenseDate)->toDateString(),
                'category' => 'Teacher Payment',
                'amount' => $amount,
                'description' => trim($this->note) !== '' ? $this->note : "Payment for {$count} classes for {$teacher->name} ({$monthDate->format('M Y')})",
                'recorded_by' => auth()->id(),
            ]);

            TeacherPayment::updateOrCreate(
                [
                    'teacher_id' => $teacherId,
                    'payout_month' => $monthDate,
                ],
                [
                    'class_count' => $count,
                    'amount' => $amount,
                    'expense_id' => $expense->id,
                    'note' => $this->note,
                    'logged_at' => now(),
                ]
            );

            AuditLog::record(
                'payout.log',
                "Teacher payout logged for {$teacher->name} ({$monthDate->format('M Y')})",
                $expense,
                [
                    'teacher_id' => $teacherId,
                    'classes' => $count,
                    'amount' => $amount,
                    'expense_id' => $expense->id,
                    'payout_month' => $monthDate->toDateString(),
                ]
            );
            $savedAny = true;
        }

        if ($savedAny) {
            $this->classCounts = [];
            $this->note = '';
            $this->saved = true;
            $this->dispatch('notify', message: 'Teacher payments recorded to ledger.');
        }
    }

    public function updatedPayoutMonth(): void
    {
        $this->classCounts = [];
        $this->saved = false;
    }
}
