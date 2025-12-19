<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Ledger</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #efefef; }
    </style>
</head>
<body>
    <h1>Finance Ledger</h1>
    <p>Period: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
    @php($manualIncomes = $manualIncomes ?? collect())

    <h3>Income</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Amount (৳)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                    <td>
                        {{ $payment->student->name }}
                        ({{ \App\Support\AcademyOptions::classLabel($payment->student->class_level ?? '') }},
                        {{ \App\Support\AcademyOptions::sectionLabel($payment->student->section ?? '') }})
                        - {{ $payment->payment_mode }}
                        @if($payment->receipt_number)
                            | Receipt #{{ $payment->receipt_number }}
                        @endif
                    </td>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No payments.</td></tr>
            @endforelse

            @forelse ($manualIncomes as $income)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($income->income_date)->format('d M Y') }}</td>
                    <td>Manual Income - {{ $income->category }} @if($income->description) ({{ $income->description }}) @endif</td>
                    <td>{{ number_format($income->amount, 2) }}</td>
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>

    <p><strong>Total Income:</strong> ৳ {{ number_format($incomeTotal, 2) }}</p>

    <h3>Expenses</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount (৳)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                    <td>{{ $expense->category }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ number_format($expense->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No expenses.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p><strong>Total Expense:</strong> ৳ {{ number_format($expenseTotal, 2) }}</p>
    <p><strong>Net:</strong> ৳ {{ number_format($incomeTotal - $expenseTotal, 2) }}</p>
</body>
</html>
