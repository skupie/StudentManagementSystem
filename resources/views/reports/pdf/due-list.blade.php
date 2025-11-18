<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Due List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #efefef; }
        .total { text-align: right; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Student Due List</h1>
    <p>
        Filters — Class: {{ $filters['class'] }} |
        Section: {{ $filters['section'] }} |
        Year: {{ $filters['year'] ?: 'All' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Section</th>
                <th>Phone</th>
                <th>Outstanding (৳)</th>
                <th>Due Months</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ \App\Support\AcademyOptions::classLabel($student->class_level) }}</td>
                    <td>{{ \App\Support\AcademyOptions::sectionLabel($student->section) }}</td>
                    <td>{{ $student->phone_number }}</td>
                    <td>{{ number_format($student->outstanding, 2) }}</td>
                    <td>{{ $student->due_months }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No outstanding dues found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="total">Total Due: ৳ {{ number_format($totalDue, 2) }}</p>
</body>
</html>
