<?php

namespace App\Livewire\Students;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TransferBoard extends Component
{
    public ?string $alert = null;
    public bool $confirmingTransfer = false;

    public function render()
    {
        $hsc1 = Student::query()
            ->where('class_level', 'hsc_1')
            ->where('is_passed', false)
            ->select('section', DB::raw('count(*) as total'))
            ->groupBy('section')
            ->pluck('total', 'section');

        $hsc2 = Student::query()
            ->where('class_level', 'hsc_2')
            ->where('is_passed', false)
            ->select('section', DB::raw('count(*) as total'))
            ->groupBy('section')
            ->pluck('total', 'section');

        return view('livewire.students.transfer-board', [
            'hscOneCounts' => $hsc1,
            'hscTwoCounts' => $hsc2,
        ]);
    }

    public function promptTransfer(): void
    {
        $this->confirmingTransfer = true;
    }

    public function cancelTransfer(): void
    {
        $this->confirmingTransfer = false;
    }

    public function transfer(): void
    {
        $this->confirmingTransfer = false;
        Student::query()
            ->where('class_level', 'hsc_1')
            ->where('is_passed', false)
            ->update(['class_level' => 'hsc_2']);

        $this->alert = 'Congratulation! Students are promoted to HSC 2nd Year.';
        $this->dispatch('notify', message: 'All HSC 1st Year students transferred to HSC 2nd Year.');
    }
}
