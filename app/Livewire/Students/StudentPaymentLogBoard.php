<?php

namespace App\Livewire\Students;

use App\Models\Student;
use Livewire\Component;
use Livewire\WithPagination;

class StudentPaymentLogBoard extends Component
{
    use WithPagination;

    public function render()
    {
        $student = $this->resolveStudent();
        if (! $student) {
            return view('livewire.students.student-payment-log-board', [
                'student' => null,
                'payments' => collect(),
            ]);
        }

        $payments = $student->feePayments()
            ->with('invoice')
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.students.student-payment-log-board', [
            'student' => $student,
            'payments' => $payments,
        ]);
    }

    protected function resolveStudent(): ?Student
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (! empty($user->studentProfile)) {
            return $user->studentProfile;
        }

        if (! empty($user->contact_number)) {
            $student = Student::where('phone_number', $user->contact_number)->first();
            if ($student) {
                return $student;
            }
        }

        return null;
    }
}
