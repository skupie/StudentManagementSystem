<?php

namespace App\Livewire\Students;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class TransferBoard extends Component
{
    public ?string $alert = null;
    public bool $confirmingTransfer = false;
    public bool $confirmingRevert = false;
    public bool $confirmingPassAll = false;
    public bool $confirmingUnpass = false;
    public array $lastPromotedIds = [];
    public array $lastPassedIds = [];
    public bool $pinVerified = false;
    public string $pinInput = '';

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

        $graduated = Student::query()
            ->where('class_level', 'hsc_2')
            ->where('is_passed', true)
            ->select('section', DB::raw('count(*) as total'))
            ->groupBy('section')
            ->pluck('total', 'section');

        return view('livewire.students.transfer-board', [
            'hscOneCounts' => $hsc1,
            'hscTwoCounts' => $hsc2,
            'graduatedCounts' => $graduated,
        ]);
    }

    public function verifyPin(): void
    {
        if (hash_equals($this->pinCode(), trim($this->pinInput))) {
            $this->pinVerified = true;
            $this->pinInput = '';
            $this->alert = null;
        } else {
            $this->alert = 'Invalid PIN. Please try again.';
            $this->dispatch('notify', message: 'Invalid PIN.');
            $this->pinVerified = false;
        }
    }

    protected function pinCode(): string
    {
        $cached = Cache::get('transfer_pin_override');
        if ($cached) {
            return (string) $cached;
        }

        return (string) config('app.transfer_pin', env('TRANSFER_PIN', '1234'));
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

    public function promptPassAll(): void
    {
        $this->confirmingPassAll = true;
    }

    public function cancelPassAll(): void
    {
        $this->confirmingPassAll = false;
    }

    public function promptUnpass(): void
    {
        $this->confirmingUnpass = true;
    }

    public function cancelUnpass(): void
    {
        $this->confirmingUnpass = false;
    }

    public function passAll(): void
    {
        $this->confirmingPassAll = false;

        $ids = Student::query()
            ->where('class_level', 'hsc_2')
            ->where('is_passed', false)
            ->pluck('id')
            ->toArray();

        if (! empty($ids)) {
            $year = now()->year;
            Student::whereIn('id', $ids)->update([
                'is_passed' => true,
                'passed_year' => $year,
            ]);
            $this->lastPassedIds = $ids;
            $this->alert = 'All HSC 2nd Year students marked as passed.';
            $this->dispatch('notify', message: 'Marked all HSC 2nd Year students as passed.');
        } else {
            $this->lastPassedIds = [];
            $this->alert = 'No HSC 2nd Year students to mark as passed.';
            $this->dispatch('notify', message: 'No HSC 2nd Year students found to pass.');
        }
    }

    public function revertPassed(): void
    {
        $this->confirmingUnpass = false;

        if (empty($this->lastPassedIds)) {
            $this->alert = 'Nothing to revert. Please mark students as passed first.';
            $this->dispatch('notify', message: 'No recent pass action to revert.');
            return;
        }

        Student::whereIn('id', $this->lastPassedIds)->update([
            'is_passed' => false,
            'passed_year' => null,
        ]);

        $this->alert = 'Reverted: recently passed students are now active again.';
        $this->dispatch('notify', message: 'Reverted pass status for recent students.');
        $this->lastPassedIds = [];
    }
}
