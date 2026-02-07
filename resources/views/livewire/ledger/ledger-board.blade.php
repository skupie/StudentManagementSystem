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
                <p class="text-xs text-gray-500 mt-1">Includes carry-forward: ৳ {{ number_format($carryForward, 2) }}</p>
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

    <div class="grid md:grid-cols-2 gap-4 items-start">
        <div class="space-y-4">
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
                <h3 class="font-semibold text-gray-800">{{ $editingIncomeId ? 'Update Income' : 'Add Income' }}</h3>
                <div>
                    <x-input-label value="Date" />
                    <x-text-input type="date" wire:model.defer="incomeForm.income_date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('incomeForm.income_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Category" />
                    <x-text-input type="text" wire:model.defer="incomeForm.category" class="mt-1 block w-full" placeholder="e.g., Donation, Misc Income" />
                    <x-input-error :messages="$errors->get('incomeForm.category')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Amount" />
                    <x-text-input type="number" step="0.01" wire:model.defer="incomeForm.amount" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('incomeForm.amount')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Description" />
                    <x-text-input type="text" wire:model.defer="incomeForm.description" class="mt-1 block w-full" />
                </div>
                <div class="text-right space-x-2">
                    @if ($editingIncomeId)
                        <x-secondary-button type="button" wire:click="resetIncomeForm">Cancel</x-secondary-button>
                    @endif
                    <x-primary-button type="button" wire:click="saveIncome">
                        {{ $editingIncomeId ? 'Update Income' : 'Save Income' }}
                    </x-primary-button>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <h3 class="font-semibold text-gray-800">Recent Payments</h3>
            <ul class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <li class="py-3 flex justify-between">
                        @if ($payment['type'] === 'fee')
                            @php($item = $payment['model'])
                            <div>
                                <div class="font-semibold text-gray-900">{{ $item->student->name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \App\Support\AcademyOptions::classLabel($item->student->class_level ?? '') }}
                                    • {{ \App\Support\AcademyOptions::sectionLabel($item->student->section ?? '') }}
                                </div>
                                <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($item->payment_date)->format('d M Y') }} • {{ $item->payment_mode }}</div>
                                <div class="text-xs text-gray-500">
                                    Receipt # {{ $item->receipt_number ?? 'N/A' }}
                                    @if ($item->invoice?->billing_month)
                                        • {{ \Carbon\Carbon::parse($item->invoice->billing_month)->format('M Y') }}
                                    @endif
                                </div>
                                @php($ledgerScholarship = optional($item->invoice)->scholarship_amount ?? 0)
                                @if ($ledgerScholarship > 0)
                                    <div class="text-xs text-blue-600">
                                        Scholarship ৳ {{ number_format($ledgerScholarship, 2) }} (Base ৳ {{ number_format(optional($item->invoice)->gross_amount ?? 0, 2) }})
                                    </div>
                                @endif
                            </div>
                            <div class="font-semibold text-green-600">৳ {{ number_format($item->amount, 2) }}</div>
                        @else
                            @php($item = $payment['model'])
                            <div>
                                <div class="font-semibold text-gray-900">Admission Fee</div>
                                <div class="text-xs text-gray-500">{{ $item->description }}</div>
                                @if (!empty($item->receipt_number))
                                    <div class="text-xs text-gray-500">Receipt # {{ $item->receipt_number }}</div>
                                @endif
                                <div class="text-xs text-gray-500">{{ optional($item->income_date)->format('d M Y') }}</div>
                            </div>
                            <div class="font-semibold text-green-600">৳ {{ number_format($item->amount, 2) }}</div>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-gray-500 text-sm text-center">No payments in range.</li>
                @endforelse
            </ul>
            <div>
                {{ $payments->links() }}
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Recent Expense and Income</h3>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-600">Filter:</span>
                <select wire:model.live="entryTypeFilter" class="rounded-md border-gray-300 text-sm">
                    <option value="all">All</option>
                    <option value="expense">Expenses</option>
                    <option value="income">Income</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Amount</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        @php($item = $entry['model'])
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ optional($entry['date'])->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ $item->category }}</td>
                            <td class="px-4 py-2">
                                @if ($entry['type'] === 'expense')
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Expense</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Income</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 {{ $entry['type'] === 'expense' ? 'text-red-600' : 'text-green-600 font-semibold' }}">
                                ৳ {{ number_format($item->amount, 2) }}
                            </td>
                            <td class="px-4 py-2">{{ $item->description }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                @if ($entry['type'] === 'expense')
                                    <x-secondary-button type="button" wire:click="editExpense({{ $item->id }})" class="text-xs">Edit</x-secondary-button>
                                    <x-danger-button type="button" wire:click="promptDelete({{ $item->id }})" class="text-xs">Delete</x-danger-button>
                                @else
                                    <x-secondary-button type="button" wire:click="editIncome({{ $item->id }})" class="text-xs">Edit</x-secondary-button>
                                    <x-danger-button type="button" wire:click="promptDeleteIncome({{ $item->id }})" class="text-xs">Delete</x-danger-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="text-sm text-gray-500">
            {{ $entries->links() }}
        </div>
    </div>

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Deletion</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700" wire:click="cancelDelete">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Are you sure you want to delete this expense? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="deleteExpense">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingIncomeDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Confirm Income Deletion</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700" wire:click="cancelIncomeDelete">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Are you sure you want to delete this income entry? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelIncomeDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="deleteIncome">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
