<?php

namespace App\Livewire\Teachers;

use App\Models\Teacher;
use App\Models\TeacherPayment;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherTransactionLogBoard extends Component
{
    use WithPagination;

    public string $monthFilter = 'all';

    public function updatedMonthFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teacher = $this->resolveTeacher();
        if (! $teacher) {
            return view('livewire.teachers.teacher-transaction-log-board', [
                'teacher' => null,
                'payments' => collect(),
                'monthOptions' => [],
                'totalAmount' => 0,
            ]);
        }

        $baseQuery = TeacherPayment::query()
            ->with('expense')
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('expense_id');

        $monthOptions = (clone $baseQuery)
            ->orderByDesc('payout_month')
            ->get(['payout_month'])
            ->map(function (TeacherPayment $payment): array {
                $monthKey = optional($payment->payout_month)->format('Y-m');
                $monthLabel = optional($payment->payout_month)->format('M Y');

                return [
                    'key' => (string) $monthKey,
                    'label' => (string) $monthLabel,
                ];
            })
            ->filter(fn (array $item) => $item['key'] !== '')
            ->unique('key')
            ->values()
            ->pluck('label', 'key')
            ->toArray();

        $paymentsQuery = (clone $baseQuery)
            ->when($this->monthFilter !== 'all', function ($query) {
                $query->whereYear('payout_month', (int) substr($this->monthFilter, 0, 4))
                    ->whereMonth('payout_month', (int) substr($this->monthFilter, 5, 2));
            })
            ->latest('payout_month')
            ->latest('id');

        $payments = $paymentsQuery->paginate(12);
        $totalAmount = (float) (clone $paymentsQuery)->sum('amount');

        return view('livewire.teachers.teacher-transaction-log-board', [
            'teacher' => $teacher,
            'payments' => $payments,
            'monthOptions' => $monthOptions,
            'totalAmount' => $totalAmount,
        ]);
    }

    protected function resolveTeacher(): ?Teacher
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (Schema::hasColumn('teachers', 'user_id')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                return $teacher;
            }
        }

        if (! empty($user->contact_number)) {
            $teacher = Teacher::where('contact_number', $user->contact_number)->first();
            if ($teacher) {
                return $teacher;
            }
        }

        return Teacher::where('name', $user->name)->first();
    }
}
