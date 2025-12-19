<?php

namespace App\Livewire\Teachers;

use App\Models\Expense;
use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\AuditLog;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class TeacherPaymentCalculator extends Component
{
    public array $classCounts = [];
    public string $expenseDate;
    public string $note = '';
    public bool $saved = false;
    public string $payoutMonth;
    public ?int $confirmingDeletePaymentId = null;

    public function mount(): void
    {
        $this->expenseDate = now()->format('Y-m-d');
        $this->payoutMonth = now()->format('Y-m');
    }

    public function render()
    {
        $monthDate = \Carbon\Carbon::parse($this->payoutMonth . '-01')->startOfMonth();
        $existing = TeacherPayment::whereDate('payout_month', $monthDate)->get()->keyBy('teacher_id');

        $teachers = Teacher::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Teacher $teacher) use ($existing) {
                $existingPayment = $existing[$teacher->id] ?? null;
                $inputCount = $this->classCounts[$teacher->id] ?? 0;
                $count = (int) $inputCount;
                $newAmount = round($count * (float) ($teacher->payment ?? 0), 2);
                $teacher->calculated_total = $newAmount;
                $teacher->total_with_existing = $newAmount + ($existingPayment->amount ?? 0);
                $teacher->input_count = $count;
                $teacher->existing_payment = $existingPayment;
                return $teacher;
            });

        $grandTotal = $teachers->sum(fn ($t) => $t->total_with_existing ?? 0);

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

            $existingPayment = TeacherPayment::where('teacher_id', $teacherId)
                ->whereDate('payout_month', $monthDate)
                ->first();

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
                    'class_count' => $count + ($existingPayment->class_count ?? 0),
                    'amount' => $amount + ($existingPayment->amount ?? 0),
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

    public function promptDeletePayment(int $teacherPaymentId): void
    {
        $this->confirmingDeletePaymentId = $teacherPaymentId;
    }

    public function cancelDeletePayment(): void
    {
        $this->confirmingDeletePaymentId = null;
    }

    public function deletePayment(int $teacherPaymentId = null): void
    {
        $id = $teacherPaymentId ?? $this->confirmingDeletePaymentId;
        if (! $id) {
            return;
        }

        $payment = TeacherPayment::find($id);
        if (! $payment) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $teacher = Teacher::find($payment->teacher_id);
            $expenseId = $payment->expense_id;
            $amount = $payment->amount;
            $classes = $payment->class_count;
            $payoutMonth = $payment->payout_month;

            $monthStart = \Carbon\Carbon::parse($payoutMonth)->startOfMonth();
            $monthEnd = \Carbon\Carbon::parse($payoutMonth)->endOfMonth();
            $teacherName = $teacher?->name ?? '';

            $payment->delete();

            $expenseQuery = Expense::where('category', 'Teacher Payment')
                ->whereBetween('expense_date', [$monthStart, $monthEnd]);

            if ($teacherName !== '') {
                $monthLabel = $monthStart->format('M Y');
                $expenseQuery->where('description', 'like', '%' . $teacherName . '%')
                    ->where('description', 'like', '%' . $monthLabel . '%');
            } elseif ($expenseId) {
                $expenseQuery->orWhere('id', $expenseId);
            }

            $deletedExpenseIds = $expenseQuery->pluck('id')->all();
            if (! empty($deletedExpenseIds)) {
                Expense::whereIn('id', $deletedExpenseIds)->delete();
            }

            AuditLog::record(
                'payout.delete',
                'Teacher payout deleted for ' . ($teacher?->name ?? 'Teacher') . ' (' . \Carbon\Carbon::parse($payoutMonth)->format('M Y') . ')',
                null,
                [
                    'teacher_id' => $payment->teacher_id,
                    'classes' => $classes,
                    'amount' => $amount,
                    'expense_ids' => $deletedExpenseIds,
                    'payout_month' => $payoutMonth,
                ]
            );
        });

        $this->confirmingDeletePaymentId = null;
        $this->saved = false;
        $this->dispatch('notify', message: 'Teacher payment deleted.');
    }

    public function updatedPayoutMonth(): void
    {
        $this->classCounts = [];
        $this->saved = false;
    }
}
