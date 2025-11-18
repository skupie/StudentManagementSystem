<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-4 gap-3">
            <div>
                <x-input-label value="Start Date" />
                <x-text-input type="date" wire:model.live="rangeStart" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="End Date" />
                <x-text-input type="date" wire:model.live="rangeEnd" class="mt-1 block w-full" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Initial Deposit" />
                <div class="flex gap-2">
                    <x-text-input type="number" step="0.01" wire:model.defer="initialDepositInput" class="mt-1 block w-full" />
                    <x-primary-button type="button" wire:click="saveInitialDeposit">Save</x-primary-button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Current: ৳ {{ number_format($initialDeposit, 2) }}</p>
                <x-input-error :messages="$errors->get('initialDepositInput')" class="mt-1" />
            </div>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="text-sm text-gray-500">Income</div>
                <div class="text-2xl font-bold text-green-700">৳ {{ number_format($incomeTotal, 2) }}</div>
            </div>
            <div class="p-4 bg-red-50 rounded-lg">
                <div class="text-sm text-gray-500">Expenses</div>
                <div class="text-2xl font-bold text-red-700">৳ {{ number_format($expenseTotal, 2) }}</div>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg">
                <div class="text-sm text-gray-500">Net Income</div>
                <div class="text-2xl font-bold text-blue-700">৳ {{ number_format($netTotal, 2) }}</div>
                <p class="text-xs text-gray-500 mt-1">Includes deposit of ৳ {{ number_format($initialDeposit, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <h3 class="font-semibold text-gray-800">{{ $editingExpenseId ? 'Update Expense' : 'Add Expense' }}</h3>
            <div>
                <x-input-label value="Date" />
                <x-text-input type="date" wire:model.defer="expenseForm.expense_date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('expenseForm.expense_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Category" />
                <select wire:model.defer="expenseForm.category" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select</option>
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('expenseForm.category')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Amount" />
                <x-text-input type="number" step="0.01" wire:model.defer="expenseForm.amount" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('expenseForm.amount')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Description" />
                <x-text-input type="text" wire:model.defer="expenseForm.description" class="mt-1 block w-full" />
            </div>
            <div class="text-right space-x-2">
                @if ($editingExpenseId)
                    <x-secondary-button type="button" wire:click="resetExpenseForm">Cancel</x-secondary-button>
                @endif
                <x-primary-button type="button" wire:click="saveExpense">
                    {{ $editingExpenseId ? 'Update Expense' : 'Save Expense' }}
                </x-primary-button>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <h3 class="font-semibold text-gray-800">Recent Payments</h3>
            <ul class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <li class="py-3 flex justify-between">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $payment->student->name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ \App\Support\AcademyOptions::classLabel($payment->student->class_level ?? '') }}
                                • {{ \App\Support\AcademyOptions::sectionLabel($payment->student->section ?? '') }}
                            </div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }} • {{ $payment->payment_mode }}</div>
                            <div class="text-xs text-gray-500">Receipt # {{ $payment->receipt_number ?? 'N/A' }}</div>
                            @php($ledgerScholarship = optional($payment->invoice)->scholarship_amount ?? 0)
                            @if ($ledgerScholarship > 0)
                                <div class="text-xs text-blue-600">
                                    Scholarship ৳ {{ number_format($ledgerScholarship, 2) }} (Base ৳ {{ number_format(optional($payment->invoice)->gross_amount ?? 0, 2) }})
                                </div>
                            @endif
                        </div>
                        <div class="font-semibold text-green-600">৳ {{ number_format($payment->amount, 2) }}</div>
                    </li>
                @empty
                    <li class="py-3 text-gray-500 text-sm text-center">No payments in range.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-800">Recent Expenses</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Amount</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ $expense->category }}</td>
                            <td class="px-4 py-2 text-red-600">৳ {{ number_format($expense->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ $expense->description }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <x-secondary-button type="button" wire:click="editExpense({{ $expense->id }})" class="text-xs">
                                    Edit
                                </x-secondary-button>
                                <x-danger-button type="button" wire:click="deleteExpense({{ $expense->id }})" class="text-xs">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                No expenses recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
