<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Weekly Exam Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #efefef; }
    </style>
</head>
<body>
    <h1>Weekly Exam Report</h1>
    <p>
        Student: {{ $student->name }} <br>
        Class: {{ $classLabel }} | Section: {{ $sectionLabel }} <br>
        Month: {{ $monthLabel }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marks as $mark)
                <tr>
                    <td>{{ optional($mark->exam_date)->format('d M Y') }}</td>
                    <td>{{ \App\Support\AcademyOptions::subjectLabel($mark->subject) }}</td>
                    <td>{{ $mark->marks_obtained }} / {{ $mark->max_marks }}</td>
                    <td>{{ $mark->remarks }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No records found for this month.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
