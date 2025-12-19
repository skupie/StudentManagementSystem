<?php

namespace App\Livewire\Ledger;

use App\Models\Expense;
use App\Models\AuditLog;
use App\Models\ManualIncome;
use App\Models\FeePayment;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class LedgerBoard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $rangeStart;
    public string $rangeEnd;

    public string $entryTypeFilter = 'all';

    public array $expenseForm = [
        'expense_date' => '',
        'category' => '',
        'amount' => '',
        'description' => '',
    ];
    public array $incomeForm = [
        'income_date' => '',
        'category' => '',
        'amount' => '',
        'description' => '',
    ];

    public ?int $editingExpenseId = null;
    public ?int $editingIncomeId = null;
    public string $initialDepositInput = '';
    public float $initialDeposit = 0;
    public ?int $confirmingDeleteId = null;
    public ?int $confirmingIncomeDeleteId = null;

    protected function rules(): array
    {
        return $this->expenseRules() + $this->incomeRules();
    }

    protected function expenseRules(): array
    {
        return [
            'expenseForm.expense_date' => ['required', 'date'],
            'expenseForm.category' => ['required', 'string', 'max:100'],
            'expenseForm.amount' => ['required', 'numeric', 'min:0'],
            'expenseForm.description' => ['nullable', 'string'],
        ];
    }

    protected function incomeRules(): array
    {
        return [
            'incomeForm.income_date' => ['required', 'date'],
            'incomeForm.category' => ['required', 'string', 'max:100'],
            'incomeForm.amount' => ['required', 'numeric', 'min:0'],
            'incomeForm.description' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->rangeStart = now()->startOfMonth()->format('Y-m-d');
        $this->rangeEnd = now()->endOfMonth()->format('Y-m-d');
        $this->expenseForm['expense_date'] = now()->format('Y-m-d');
        $this->incomeForm['income_date'] = now()->format('Y-m-d');
        $this->loadInitialDeposit();
    }

    public function render()
    {
        $incomeQuery = FeePayment::query()
            ->with(['student', 'invoice'])
            ->whereBetween('payment_date', [$this->rangeStart, $this->rangeEnd])
            ->orderByDesc('payment_date');

        $manualIncomeQuery = ManualIncome::query()
            ->whereBetween('income_date', [$this->rangeStart, $this->rangeEnd])
            ->orderByDesc('income_date');

        $expenseQuery = Expense::query()
            ->whereBetween('expense_date', [$this->rangeStart, $this->rangeEnd])
            ->orderByDesc('expense_date');

        $incomeTotal = (clone $incomeQuery)->sum('amount') + (clone $manualIncomeQuery)->sum('amount');
        $expenseTotal = (clone $expenseQuery)->sum('amount');

        $rangeStartDate = Carbon::parse($this->rangeStart);
        $prevStart = $rangeStartDate->copy()->subMonth()->startOfMonth();
        $prevEnd = $rangeStartDate->copy()->subMonth()->endOfMonth();
        $carryForward = (FeePayment::whereBetween('payment_date', [$prevStart, $prevEnd])->sum('amount')
            + ManualIncome::whereBetween('income_date', [$prevStart, $prevEnd])->sum('amount'))
            - Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount');

        $payments = $incomeQuery->paginate(15, ['*'], 'paymentsPage');
        $expenses = $expenseQuery->get();
        $manualIncomes = $manualIncomeQuery->get();

        $combined = collect();
        foreach ($expenses as $expense) {
            $combined->push([
                'type' => 'expense',
                'date' => $expense->expense_date,
                'model' => $expense,
            ]);
        }
        foreach ($manualIncomes as $income) {
            $combined->push([
                'type' => 'income',
                'date' => $income->income_date,
                'model' => $income,
            ]);
        }

        $filteredCombined = $combined->filter(function ($entry) {
            if ($this->entryTypeFilter === 'all') {
                return true;
            }
            return $entry['type'] === $this->entryTypeFilter;
        });

        $sortedCombined = $filteredCombined->sortByDesc('date')->values();
        $page = LengthAwarePaginator::resolveCurrentPage('entriesPage');
        $perPage = 15;
        $entries = new LengthAwarePaginator(
            $sortedCombined->forPage($page, $perPage),
            $sortedCombined->count(),
            $perPage,
            $page,
            ['pageName' => 'entriesPage']
        );

        return view('livewire.ledger.ledger-board', [
            'payments' => $payments,
            'entries' => $entries,
            'incomeTotal' => $incomeTotal + $carryForward,
            'expenseTotal' => $expenseTotal,
            'carryForward' => $carryForward,
            'netTotal' => ($carryForward + $incomeTotal) - $expenseTotal,
            'expenseCategories' => config('academy.expense_categories'),
            'initialDeposit' => $this->initialDeposit,
        ]);
    }

    public function saveExpense(): void
    {
        $data = $this->validate($this->expenseRules())['expenseForm'];
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

    public function saveIncome(): void
    {
        $data = $this->validate($this->incomeRules())['incomeForm'];
        $data['recorded_by'] = auth()->id();

        $income = ManualIncome::updateOrCreate(
            ['id' => $this->editingIncomeId],
            $data
        );

        AuditLog::record(
            $this->editingIncomeId ? 'income.update' : 'income.create',
            ($this->editingIncomeId ? 'Updated' : 'Added') . ' income: ' . $data['category'] . ' ৳' . $data['amount'],
            $income,
            [
                'income_id' => $income->id,
                'date' => $data['income_date'],
                'category' => $data['category'],
                'amount' => $data['amount'],
                'description' => $data['description'],
            ]
        );

        $this->resetIncomeForm();

        if (strtolower($income->category ?? '') === 'initial deposit') {
            $this->syncInitialDepositFromIncomes();
        }
    }

    public function editIncome(int $incomeId): void
    {
        $income = ManualIncome::findOrFail($incomeId);
        $this->editingIncomeId = $income->id;
        $this->incomeForm = [
            'income_date' => $income->income_date->format('Y-m-d'),
            'category' => $income->category,
            'amount' => $income->amount,
            'description' => $income->description,
        ];
    }

    public function promptDelete(int $expenseId): void
    {
        $this->confirmingDeleteId = $expenseId;
    }

    public function promptDeleteIncome(int $incomeId): void
    {
        $this->confirmingIncomeDeleteId = $incomeId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function cancelIncomeDelete(): void
    {
        $this->confirmingIncomeDeleteId = null;
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

    public function deleteIncome(int $incomeId = null): void
    {
        $id = $incomeId ?? $this->confirmingIncomeDeleteId;
        if (! $id) {
            return;
        }

        $income = ManualIncome::find($id);
        if (! $income) {
            return;
        }

        AuditLog::record(
            'income.delete',
            'Deleted income: ' . $income->category . ' ৳' . $income->amount,
            $income,
            [
                'income_id' => $income->id,
                'date' => optional($income->income_date)->toDateString(),
                'category' => $income->category,
                'amount' => $income->amount,
                'description' => $income->description,
            ]
        );

        $income->delete();
        $this->confirmingIncomeDeleteId = null;

        if (strtolower($income->category ?? '') === 'initial deposit') {
            $this->syncInitialDepositFromIncomes();
        }
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

    public function resetIncomeForm(): void
    {
        $this->editingIncomeId = null;
        $this->incomeForm = [
            'income_date' => now()->format('Y-m-d'),
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

        $amount = (float) $this->initialDepositInput;

        AuditLog::record(
            'ledger.initial_deposit.update',
            'Initial deposit set to ৳' . $amount,
            null,
            ['amount' => $amount]
        );

        $depositDate = Carbon::parse($this->rangeStart)->startOfMonth()->toDateString();

        ManualIncome::create([
            'income_date' => $depositDate,
            'category' => 'Initial Deposit',
            'amount' => $amount,
            'description' => 'Initial deposit added to ledger',
            'recorded_by' => auth()->id(),
        ]);

        $this->syncInitialDepositFromIncomes();
        $this->initialDepositInput = '';
        $this->dispatch('notify', message: 'Initial deposit saved and logged as income.');
    }

    public function updatedRangeStart(): void
    {
        $this->resetPage('paymentsPage');
        $this->resetPage('entriesPage');
        $this->syncInitialDepositFromIncomes();
    }

    public function updatedRangeEnd(): void
    {
        $this->resetPage('paymentsPage');
        $this->resetPage('entriesPage');
        $this->syncInitialDepositFromIncomes();
    }

    public function updatedEntryTypeFilter(): void
    {
        $this->resetPage('entriesPage');
    }

    protected function loadInitialDeposit(): void
    {
        $this->syncInitialDepositFromIncomes();
        $this->initialDepositInput = '';
    }

    protected function syncInitialDepositFromIncomes(): void
    {
        $start = Carbon::parse($this->rangeStart)->startOfMonth();
        $end = Carbon::parse($this->rangeEnd)->endOfMonth();
        $total = (float) ManualIncome::where('category', 'Initial Deposit')
            ->whereBetween('income_date', [$start, $end])
            ->sum('amount');

        $this->initialDeposit = $total;
        Setting::setValue('ledger_initial_deposit', $total);
    }
}
