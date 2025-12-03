<?php

namespace App\Livewire\Teachers;

use App\Models\Expense;
use App\Models\Teacher;
use Livewire\Component;

class TeacherPaymentCalculator extends Component
{
    public array $classCounts = [];
    public string $expenseDate;
    public string $note = '';
    public bool $saved = false;

    public function mount(): void
    {
        $this->expenseDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $teachers = Teacher::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Teacher $teacher) {
                $count = (int) ($this->classCounts[$teacher->id] ?? 0);
                $teacher->calculated_total = round($count * (float) ($teacher->payment ?? 0), 2);
                $teacher->input_count = $count;
                return $teacher;
            });

        $grandTotal = $teachers->sum('calculated_total');

        return view('livewire.teachers.teacher-payment-calculator', [
            'teachers' => $teachers,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function save(): void
    {
        $teachers = Teacher::whereIn('id', array_keys($this->classCounts))->get()->keyBy('id');
        $entries = [];
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

            $entries[] = [
                'expense_date' => $this->expenseDate,
                'category' => 'Teacher Payment',
                'amount' => $amount,
                'description' => trim($this->note) !== '' ? $this->note : "Payment for {$count} classes — {$teacher->name}",
                'recorded_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($entries)) {
            return;
        }

        Expense::insert($entries);
        $this->classCounts = [];
        $this->note = '';
        $this->saved = true;
        $this->dispatch('notify', message: 'Teacher payments recorded to ledger.');
    }
}
