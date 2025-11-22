<?php

namespace App\Livewire\Holidays;

use App\Models\Holiday;
use Livewire\Component;
use Livewire\WithPagination;

class HolidayManager extends Component
{
    use WithPagination;

    public string $holidayDate = '';
    public string $reason = '';

    protected function rules(): array
    {
        return [
            'holidayDate' => ['required', 'date', 'unique:holidays,holiday_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function render()
    {
        return view('livewire.holidays.holiday-manager', [
            'holidays' => Holiday::orderBy('holiday_date', 'desc')->paginate(12),
        ]);
    }

    public function save(): void
    {
        $this->validate();

        Holiday::create([
            'holiday_date' => $this->holidayDate,
            'reason' => $this->reason,
            'created_by' => auth()->id(),
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: 'Holiday saved.');
    }

    public function delete(int $holidayId): void
    {
        Holiday::where('id', $holidayId)->delete();
        $this->dispatch('notify', message: 'Holiday removed.');
    }

    protected function resetForm(): void
    {
        $this->holidayDate = '';
        $this->reason = '';
    }
}
