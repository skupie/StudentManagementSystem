<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weekly Exam Report</title>
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
    <p>Class: {{ $classLabel }} | Section: {{ $sectionLabel }} | Subject: {{ $subjectLabel }} | Date: {{ optional($date)->format('d M Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marks as $mark)
                <tr>
                    <td>{{ $mark->student->name }}</td>
                    <td>{{ \App\Support\AcademyOptions::subjectLabel($mark->subject) }}</td>
                    <td>{{ $mark->marks_obtained }} / {{ $mark->max_marks }}</td>
                    <td>{{ $mark->remarks }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No marks found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
