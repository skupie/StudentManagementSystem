<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportController extends Controller
{
    public function weeklyExams(Request $request)
    {
        $class = $request->input('class', 'hsc_1');
        $section = $request->input('section', 'science');
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput) : null;
        $subject = $request->input('subject', 'all');

        $marks = WeeklyExamMark::with('student')
            ->where('class_level', $class)
            ->where('section', $section)
            ->when($subject !== 'all', fn ($q) => $q->where('subject', $subject))
            ->when($date, fn ($q) => $q->whereDate('exam_date', $date))
            ->orderBy('exam_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.weekly-exams', [
            'marks' => $marks,
            'classLabel' => AcademyOptions::classLabel($class),
            'sectionLabel' => AcademyOptions::sectionLabel($section),
            'date' => $date,
            'subject' => $subject,
            'subjectLabel' => $subject === 'all' ? 'All Subjects' : AcademyOptions::subjectLabel($subject),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("weekly-exams-{$class}-{$section}.pdf");
    }
    public function weeklyExamsExcel(Request $request)
    {
        $class = $request->input('class', 'hsc_1');
        $section = $request->input('section', 'science');
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput) : null;
        $subject = $request->input('subject', 'all');

        $marks = WeeklyExamMark::with('student')
            ->where('class_level', $class)
            ->where('section', $section)
            ->when($subject !== 'all', fn ($q) => $q->where('subject', $subject))
            ->when($date, fn ($q) => $q->whereDate('exam_date', $date))
            ->orderBy('exam_date')
            ->get();

        $filename = 'weekly-exams-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        $columns = ['Student', 'Class', 'Section', 'Subject', 'Exam Date', 'Marks', 'Max Marks', 'Remarks'];

        $callback = function () use ($columns, $marks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($marks as $mark) {
                fputcsv($handle, [
                    $mark->student->name,
                    AcademyOptions::classLabel($mark->class_level),
                    AcademyOptions::sectionLabel($mark->section),
                    AcademyOptions::subjectLabel($mark->subject),
                    optional($mark->exam_date)->format('d M Y'),
                    $mark->marks_obtained,
                    $mark->max_marks,
                    $mark->remarks,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function dueList(Request $request)
    {
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');
        $year = $request->input('year', '');

        $students = Student::query()
            ->with(['feePayments' => fn ($q) => $q->orderBy('payment_date')])
            ->when($class !== 'all', fn ($q) => $q->where('class_level', $class))
            ->when($section !== 'all', fn ($q) => $q->where('section', $section))
            ->when($year, fn ($q) => $q->where('academic_year', 'like', '%' . $year . '%'))
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $summary = $student->dueSummary();
                $student->outstanding = $summary['amount'];
                $student->due_months = implode(', ', $summary['months']);
                return $student;
            })
            ->filter(fn (Student $student) => $student->outstanding > 0)
            ->values();

        $pdf = Pdf::loadView('reports.pdf.due-list', [
            'students' => $students,
            'filters' => [
                'class' => $class,
                'section' => $section,
                'year' => $year,
            ],
            'totalDue' => $students->sum('outstanding'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('due-list.pdf');
    }

    public function finance(Request $request)
    {
        $startInput = $request->input('start');
        $endInput = $request->input('end');

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : now()->startOfMonth();
        $end = $endInput ? Carbon::parse($endInput)->endOfDay() : now()->endOfMonth();

        $payments = FeePayment::with('student')
            ->whereBetween('payment_date', [$start, $end])
            ->orderBy('payment_date')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.finance', [
            'payments' => $payments,
            'expenses' => $expenses,
            'start' => $start,
            'end' => $end,
            'incomeTotal' => $payments->sum('amount'),
            'expenseTotal' => $expenses->sum('amount'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('finance-ledger.pdf');
    }

    public function financeExcel(Request $request)
    {
        $startInput = $request->input('start');
        $endInput = $request->input('end');

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : now()->startOfMonth();
        $end = $endInput ? Carbon::parse($endInput)->endOfDay() : now()->endOfMonth();

        $payments = FeePayment::with('student')
            ->whereBetween('payment_date', [$start, $end])
            ->orderBy('payment_date')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date')
            ->get();

        $filename = 'finance-ledger-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($payments, $expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'Date', 'Description', 'Amount', 'Receipt']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    'Income',
                    optional($payment->payment_date)->format('d M Y'),
                    $payment->student->name . ' (' . AcademyOptions::classLabel($payment->student->class_level ?? '') . ', ' . AcademyOptions::sectionLabel($payment->student->section ?? '') . ') - ' . ($payment->payment_mode ?? 'N/A'),
                    number_format($payment->amount, 2),
                    $payment->receipt_number ?? 'N/A',
                ]);
            }

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    'Expense',
                    optional($expense->expense_date)->format('d M Y'),
                    $expense->category . ($expense->description ? ' - ' . $expense->description : ''),
                    number_format($expense->amount, 2),
                    '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function dueListExcel(Request $request)
    {
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');
        $year = $request->input('year', '');

        $students = Student::query()
            ->with(['feePayments' => fn ($q) => $q->orderBy('payment_date')])
            ->when($class !== 'all', fn ($q) => $q->where('class_level', $class))
            ->when($section !== 'all', fn ($q) => $q->where('section', $section))
            ->when($year, fn ($q) => $q->where('academic_year', 'like', '%' . $year . '%'))
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $summary = $student->dueSummary();
                return [
                    'name' => $student->name,
                    'class' => AcademyOptions::classLabel($student->class_level),
                    'section' => AcademyOptions::sectionLabel($student->section),
                    'phone' => $student->phone_number,
                    'outstanding' => $summary['amount'],
                    'due_months' => implode(', ', $summary['months']),
                ];
            })
            ->filter(fn ($row) => $row['outstanding'] > 0)
            ->values();

        $filename = 'due-list-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Student Name', 'Class', 'Section', 'Phone', 'Outstanding', 'Due Months'];

        $callback = function () use ($columns, $students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($students as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['class'],
                    $row['section'],
                    $row['phone'],
                    number_format($row['outstanding'], 2),
                    $row['due_months'],
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function weeklyExamsStudent(Request $request)
    {
        $class = $request->input('class');
        $section = $request->input('section');
        $studentId = $request->input('student_id');
        $month = $request->input('month', now()->format('Y-m'));

        abort_if(! $class || ! $section || ! $studentId, 404);

        $student = Student::where('class_level', $class)
            ->where('section', $section)
            ->findOrFail($studentId);

        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $marks = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->whereBetween('exam_date', [$start, $end])
            ->orderBy('exam_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.student-weekly-exams', [
            'student' => $student,
            'classLabel' => AcademyOptions::classLabel($class),
            'sectionLabel' => AcademyOptions::sectionLabel($section),
            'monthLabel' => $monthDate->format('F Y'),
            'marks' => $marks,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("weekly-exams-{$student->id}-{$monthDate->format('Ym')}.pdf");
    }

    public function weeklyExamsStudentExcel(Request $request)
    {
        $class = $request->input('class');
        $section = $request->input('section');
        $studentId = $request->input('student_id');
        $month = $request->input('month', now()->format('Y-m'));

        abort_if(! $class || ! $section || ! $studentId, 404);

        $student = Student::where('class_level', $class)
            ->where('section', $section)
            ->findOrFail($studentId);

        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $marks = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->whereBetween('exam_date', [$start, $end])
            ->orderBy('exam_date')
            ->get();

        $filename = 'weekly-exams-' . $student->id . '-' . $monthDate->format('Ym') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Date', 'Subject', 'Marks', 'Max Marks', 'Remarks'];

        $callback = function () use ($columns, $marks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($marks as $mark) {
                fputcsv($handle, [
                    optional($mark->exam_date)->format('d M Y'),
                    AcademyOptions::subjectLabel($mark->subject),
                    $mark->marks_obtained,
                    $mark->max_marks,
                    $mark->remarks,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function attendancePdf(Request $request)
    {
        $dateInput = $request->input('date', now()->toDateString());
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');

        $date = Carbon::parse($dateInput)->startOfDay();

        $records = Attendance::query()
            ->with(['student', 'linkedNote'])
            ->whereDate('attendance_date', $date->toDateString())
            ->when($class !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_level', $class)))
            ->when($section !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section', $section)))
            ->orderBy(Attendance::select('name')->from('students')->whereColumn('students.id', 'attendances.student_id'))
            ->get();

        $pdf = Pdf::loadView('reports.pdf.attendance', [
            'records' => $records,
            'date' => $date,
            'classLabel' => $class === 'all' ? 'All' : AcademyOptions::classLabel($class),
            'sectionLabel' => $section === 'all' ? 'All' : AcademyOptions::sectionLabel($section),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('attendance-' . $date->format('Ymd') . '.pdf');
    }

    public function attendanceExcel(Request $request)
    {
        $dateInput = $request->input('date', now()->toDateString());
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');

        $date = Carbon::parse($dateInput)->startOfDay();

        $records = Attendance::query()
            ->with(['student', 'linkedNote'])
            ->whereDate('attendance_date', $date->toDateString())
            ->when($class !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_level', $class)))
            ->when($section !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section', $section)))
            ->orderBy(Attendance::select('name')->from('students')->whereColumn('students.id', 'attendances.student_id'))
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $columns = ['Date', 'Student', 'Class', 'Section', 'Status', 'Category', 'Note'];
        $sheet->fromArray($columns, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($records as $record) {
            $noteBody = $record->linkedNote->body ?? $record->note ?? '';
            $noteCategory = $record->linkedNote->category ?? $record->category ?? '';
            $sheet->fromArray([
                $date->format('d M Y'),
                $record->student->name ?? '',
                AcademyOptions::classLabel($record->student->class_level ?? ''),
                AcademyOptions::sectionLabel($record->student->section ?? ''),
                ucfirst($record->status ?? 'absent'),
                $noteCategory ?: 'Reason not set',
                $noteBody ?: 'No additional note provided.',
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'attendance-' . $date->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function attendanceMatrixCsv(Request $request)
    {
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');
        $academicYear = $request->input('year', '');
        $monthInput = $request->input('month', now()->format('Y-m'));

        $monthDate = Carbon::createFromFormat('Y-m', $monthInput);
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();
        $daysInMonth = $monthDate->daysInMonth;

        $students = Student::query()
            ->when($class !== 'all', fn ($q) => $q->where('class_level', $class))
            ->when($section !== 'all', fn ($q) => $q->where('section', $section))
            ->when($academicYear, fn ($q) => $q->where('academic_year', 'like', '%' . $academicYear . '%'))
            ->whereDate('enrollment_date', '<=', $end)
            ->orderBy('name')
            ->get();
        $studentIds = $students->pluck('id');
        $holidayDates = Holiday::whereBetween('holiday_date', [$start, $end])
            ->pluck('holiday_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Preload attendance for the month keyed by student and date
        $attendance = DB::table('attendances')
            ->select('student_id', 'attendance_date', 'status')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->when($studentIds->isNotEmpty(), fn ($q) => $q->whereIn('student_id', $studentIds))
            ->get()
            ->groupBy('student_id')
            ->map(function ($rows) {
                return collect($rows)->keyBy(fn ($row) => Carbon::parse($row->attendance_date)->format('Y-m-d'));
            });

        $filename = 'attendance-matrix-' . $monthDate->format('Ym') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['Student Name'];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $columns[] = $monthDate->copy()->day($day)->format('d');
        }
        $columns[] = 'Total Present Days';

        $callback = function () use ($columns, $students, $attendance, $monthDate, $daysInMonth) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);

            foreach ($students as $student) {
                $row = [$student->name];
                $presentCount = 0;

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $monthDate->copy()->day($day);
                    $key = $date->format('Y-m-d');

                    if (in_array($key, $holidayDates, true)) {
                        $row[] = 'H';
                    } elseif ($date->isFriday()) {
                        $row[] = 'W';
                    } else {
                        $record = $attendance->get($student->id)[$key] ?? null;
                        if ($record && $record->status === 'present') {
                            $row[] = 'P';
                            $presentCount++;
                        } elseif ($record && $record->status === 'absent') {
                            $row[] = 'A';
                        } else {
                            $row[] = '';
                        }
                    }
                }

                $row[] = $presentCount;
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function attendanceMatrixXlsx(Request $request)
    {
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');
        $academicYear = $request->input('year', '');
        $monthInput = $request->input('month', now()->format('Y-m'));

        $monthDate = Carbon::createFromFormat('Y-m', $monthInput);
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();
        $daysInMonth = $monthDate->daysInMonth;

        $students = Student::query()
            ->when($class !== 'all', fn ($q) => $q->where('class_level', $class))
            ->when($section !== 'all', fn ($q) => $q->where('section', $section))
            ->when($academicYear, fn ($q) => $q->where('academic_year', 'like', '%' . $academicYear . '%'))
            ->whereDate('enrollment_date', '<=', $end)
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');
        $holidayDates = Holiday::whereBetween('holiday_date', [$start, $end])
            ->pluck('holiday_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $attendance = DB::table('attendances')
            ->select('student_id', 'attendance_date', 'status')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->when($studentIds->isNotEmpty(), fn ($q) => $q->whereIn('student_id', $studentIds))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => collect($rows)->keyBy(fn ($row) => Carbon::parse($row->attendance_date)->format('Y-m-d')));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $groups = ($class !== 'all' && $section !== 'all')
            ? collect([$students])
            : $students->groupBy(fn (Student $s) => ($s->class_level ?? 'class') . '-' . ($s->section ?? 'section'));

        $header = ['Student Name'];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $header[] = $monthDate->copy()->day($day)->format('d');
        }
        $header[] = 'Total Present Days';

        foreach ($groups as $key => $group) {
            if ($group->isEmpty()) {
                continue;
            }

            $first = $group->first();
            $sheetName = ($class !== 'all' && $section !== 'all')
                ? AcademyOptions::classLabel($first->class_level ?? '') . '-' . AcademyOptions::sectionLabel($first->section ?? '')
                : (is_string($key) ? $key : 'Sheet');
            $sheetName = substr($sheetName, 0, 31);

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            // Header
            $sheet->fromArray($header, null, 'A1');
            $lastHeaderCol = Coordinate::stringFromColumnIndex(count($header));
            $sheet->getStyle('A1:' . $lastHeaderCol . '1')
                ->getFont()->setBold(true);
            $sheet->getStyle('A1:' . $lastHeaderCol . '1')
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $rowIndex = 2;
            foreach ($group as $student) {
                $row = [$student->name];
                $presentCount = 0;

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $monthDate->copy()->day($day);
                    $keyDate = $date->format('Y-m-d');
                    $cellValue = '';
                    $color = null;

                    if (in_array($keyDate, $holidayDates, true)) {
                        $cellValue = 'H';
                        $color = 'FFF472B6'; // pink
                    } elseif ($date->isFriday()) {
                        $cellValue = 'W';
                        $color = 'FFF59E0B'; // orange
                    } else {
                        $record = $attendance->get($student->id)[$keyDate] ?? null;
                        if ($record && $record->status === 'present') {
                            $cellValue = 'P';
                            $color = 'FF4ADE80'; // green
                            $presentCount++;
                        } elseif ($record && $record->status === 'absent') {
                            $cellValue = 'A';
                            $color = 'FFF87171'; // red
                        }
                    }

                    $row[] = $cellValue;
                    if ($color) {
                        $colLetter = Coordinate::stringFromColumnIndex(count($row));
                        $sheet->getStyle($colLetter . $rowIndex)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                        $sheet->getStyle($colLetter . $rowIndex)->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                $row[] = $presentCount;
                $sheet->fromArray($row, null, 'A' . $rowIndex);
                $rowIndex++;
            }

            // Auto width
            foreach (range(1, count($header)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'attendance-matrix-' . $monthDate->format('Ym') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
