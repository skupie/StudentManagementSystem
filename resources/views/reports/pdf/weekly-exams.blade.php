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
        td.absent { background: #fee2e2; color: #b91c1c; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <h1>Weekly Exam Report</h1>
    <p>Class: {{ $classLabel }} | Section: {{ $sectionLabel }} | Subject Filter: {{ $subjectLabel }} | Period: {{ $periodLabel }}</p>

    @if (empty($subjects))
        <p>No data available.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    @foreach ($subjects as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['student']->name }}</td>
                        @foreach ($subjects as $code => $label)
                            @php($cell = $row['subjects'][$code] ?? ['text' => '', 'absent' => false])
                            <td class="{{ $cell['absent'] ? 'absent' : '' }}">{{ $cell['text'] }}</td>
                        @endforeach
                        <td>{{ $row['total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($subjects) + 1 }}">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
