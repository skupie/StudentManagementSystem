<?php

namespace App\Livewire\Students;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TransferBoard extends Component
{
    public ?string $alert = null;
    public bool $confirmingTransfer = false;
    public bool $confirmingRevert = false;
    public array $lastPromotedIds = [];

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

    public function promptRevert(): void
    {
        $this->confirmingRevert = true;
    }

    public function cancelRevert(): void
    {
        $this->confirmingRevert = false;
    }

    public function transfer(): void
    {
        $this->confirmingTransfer = false;
        $ids = Student::query()
            ->where('class_level', 'hsc_1')
            ->where('is_passed', false)
            ->pluck('id')
            ->toArray();

        if (! empty($ids)) {
            Student::whereIn('id', $ids)->update(['class_level' => 'hsc_2']);
            $this->lastPromotedIds = $ids;
        } else {
            $this->lastPromotedIds = [];
        }

        $this->alert = 'Congratulation! Students are promoted to HSC 2nd Year.';
        $this->dispatch('notify', message: 'All HSC 1st Year students transferred to HSC 2nd Year.');
    }

    public function revert(): void
    {
        $this->confirmingRevert = false;
        if (empty($this->lastPromotedIds)) {
            $this->alert = 'Nothing to revert. Please promote first.';
            $this->dispatch('notify', message: 'No recent promotion to revert.');
            return;
        }

        Student::whereIn('id', $this->lastPromotedIds)
            ->where('class_level', 'hsc_2')
            ->update(['class_level' => 'hsc_1']);

        $this->alert = 'Reverted: recently promoted students moved back to HSC 1st Year.';
        $this->dispatch('notify', message: 'Transfer reverted for recently promoted students.');
        $this->lastPromotedIds = [];
    }
}
