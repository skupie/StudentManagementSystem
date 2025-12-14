<?php

namespace App\Livewire\Ledger;

use App\Models\Expense;
use App\Models\AuditLog;
use App\Models\FeePayment;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class LedgerBoard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

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
    public ?int $confirmingDeleteId = null;

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

        $rangeStartDate = Carbon::parse($this->rangeStart);
        $prevStart = $rangeStartDate->copy()->subMonth()->startOfMonth();
        $prevEnd = $rangeStartDate->copy()->subMonth()->endOfMonth();
        $carryForward = FeePayment::whereBetween('payment_date', [$prevStart, $prevEnd])->sum('amount')
            - Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount');

        $payments = $incomeQuery->paginate(15);
        $expenses = $expenseQuery->take(15)->get();

        return view('livewire.ledger.ledger-board', [
            'payments' => $payments,
            'expenses' => $expenses,
            'incomeTotal' => $incomeTotal + $carryForward,
            'expenseTotal' => $expenseTotal,
            'carryForward' => $carryForward,
            'netTotal' => ($this->initialDeposit + $carryForward + $incomeTotal) - $expenseTotal,
            'expenseCategories' => config('academy.expense_categories'),
            'initialDeposit' => $this->initialDeposit,
        ]);
    }

    public function saveExpense(): void
    {
        $data = $this->validate()['expenseForm'];
        $data['recorded_by'] = auth()->id();

        $expense = Expense::updateOrCreate(
            ['id' => $this->editingExpenseId],
            $data
        );

        AuditLog::record(
            $this->editingExpenseId ? 'expense.update' : 'expense.create',
            ($this->editingExpenseId ? 'Updated' : 'Added') . ' expense: ' . $data['category'] . ' ৳' . $data['amount'],
            $expense,
            [
                'expense_id' => $expense->id,
                'date' => $data['expense_date'],
                'category' => $data['category'],
                'amount' => $data['amount'],
                'description' => $data['description'],
            ]
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

    public function promptDelete(int $expenseId): void
    {
        $this->confirmingDeleteId = $expenseId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteExpense(int $expenseId = null): void
    {
        $id = $expenseId ?? $this->confirmingDeleteId;
        if (! $id) {
            return;
        }

        $expense = Expense::find($id);
        if (! $expense) {
            return;
        }

        AuditLog::record(
            'expense.delete',
            'Deleted expense: ' . $expense->category . ' ৳' . $expense->amount,
            $expense,
            [
                'expense_id' => $expense->id,
                'date' => optional($expense->expense_date)->toDateString(),
                'category' => $expense->category,
                'amount' => $expense->amount,
                'description' => $expense->description,
            ]
        );

        $expense->delete();
        $this->confirmingDeleteId = null;
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

        AuditLog::record(
            'ledger.initial_deposit.update',
            'Initial deposit set to ৳' . $this->initialDepositInput,
            null,
            ['amount' => $this->initialDepositInput]
        );
    }

    public function updatedRangeStart(): void
    {
        $this->resetPage();
    }

    public function updatedRangeEnd(): void
    {
        $this->resetPage();
    }

    protected function loadInitialDeposit(): void
    {
        $value = Setting::getValue('ledger_initial_deposit', '0');
        $this->initialDeposit = (float) $value;
        $this->initialDepositInput = $value;
    }
}
