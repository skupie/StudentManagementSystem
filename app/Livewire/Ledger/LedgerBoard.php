<?php

namespace App\Livewire\Ledger;

use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\Setting;
use Livewire\Component;

class LedgerBoard extends Component
{
    public string $rangeStart;
    public string $rangeEnd;

    public array $expenseForm = [
        'expense_date' => '',
        'category' => '',
        'amount' => '',
        'description' => '',
    ];

    public ?int $editingExpenseId = null;
    public string $initialDepositInput = '';
    public float $initialDeposit = 0;

    protected function rules(): array
    {
        return [
            'expenseForm.expense_date' => ['required', 'date'],
            'expenseForm.category' => ['required', 'string', 'max:100'],
            'expenseForm.amount' => ['required', 'numeric', 'min:0'],
            'expenseForm.description' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->rangeStart = now()->startOfMonth()->format('Y-m-d');
        $this->rangeEnd = now()->endOfMonth()->format('Y-m-d');
        $this->expenseForm['expense_date'] = now()->format('Y-m-d');
        $this->loadInitialDeposit();
    }

    public function render()
    {
        $incomeQuery = FeePayment::query()
            ->with(['student', 'invoice'])
            ->whereBetween('payment_date', [$this->rangeStart, $this->rangeEnd])
            ->orderByDesc('payment_date');

        $expenseQuery = Expense::query()
            ->whereBetween('expense_date', [$this->rangeStart, $this->rangeEnd])
            ->orderByDesc('expense_date');

        $incomeTotal = (clone $incomeQuery)->sum('amount');
        $expenseTotal = (clone $expenseQuery)->sum('amount');

        $payments = $incomeQuery->take(15)->get();
        $expenses = $expenseQuery->take(15)->get();

        return view('livewire.ledger.ledger-board', [
            'payments' => $payments,
            'expenses' => $expenses,
            'incomeTotal' => $incomeTotal,
            'expenseTotal' => $expenseTotal,
            'netTotal' => ($this->initialDeposit + $incomeTotal) - $expenseTotal,
            'expenseCategories' => config('academy.expense_categories'),
            'initialDeposit' => $this->initialDeposit,
        ]);
    }

    public function saveExpense(): void
    {
        $data = $this->validate()['expenseForm'];
        $data['recorded_by'] = auth()->id();

        Expense::updateOrCreate(
            ['id' => $this->editingExpenseId],
            $data
        );

        $this->resetExpenseForm();
    }

    public function editExpense(int $expenseId): void
    {
        $expense = Expense::findOrFail($expenseId);
        $this->editingExpenseId = $expense->id;
        $this->expenseForm = [
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'category' => $expense->category,
            'amount' => $expense->amount,
            'description' => $expense->description,
        ];
    }

    public function deleteExpense(int $expenseId): void
    {
        Expense::where('id', $expenseId)->delete();
    }

    public function resetExpenseForm(): void
    {
        $this->editingExpenseId = null;
        $this->expenseForm = [
            'expense_date' => now()->format('Y-m-d'),
            'category' => '',
            'amount' => '',
            'description' => '',
        ];
    }

    public function saveInitialDeposit(): void
    {
        $this->validate([
            'initialDepositInput' => ['required', 'numeric', 'min:0'],
        ]);

        Setting::setValue('ledger_initial_deposit', $this->initialDepositInput);
        $this->initialDeposit = (float) $this->initialDepositInput;
        $this->dispatch('notify', message: 'Initial deposit saved.');
    }

    protected function loadInitialDeposit(): void
    {
        $value = Setting::getValue('ledger_initial_deposit', '0');
        $this->initialDeposit = (float) $value;
        $this->initialDepositInput = $value;
    }
}
