<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #777; padding: 5px; text-align: left; }
        th { background: #efefef; }
    </style>
</head>
<body>
    <h1>Attendance Log</h1>
    <p>Date: {{ $date->format('d M Y') }}</p>
    <p>Class: {{ $classLabel }} | Section: {{ $sectionLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Section</th>
                <th>Status</th>
                <th>Category</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                @php
                    $noteBody = $record->linkedNote->body ?? $record->note ?? '';
                    $noteCategory = $record->linkedNote->category ?? $record->category ?? '';
                @endphp
                <tr>
                    <td>{{ $record->student->name ?? '' }}</td>
                    <td>{{ \App\Support\AcademyOptions::classLabel($record->student->class_level ?? '') }}</td>
                    <td>{{ \App\Support\AcademyOptions::sectionLabel($record->student->section ?? '') }}</td>
                    <td>{{ ucfirst($record->status ?? 'absent') }}</td>
                    <td>{{ $noteCategory ?: 'Reason not set' }}</td>
                    <td>{{ $noteBody ?: 'No additional note provided.' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No attendance records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
