<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Support\AcademyOptions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentExportController extends Controller
{
    public function __invoke()
    {
        $studentsByClass = Student::orderBy('class_level')
            ->orderBy('section')
            ->orderBy('name')
            ->get()
            ->groupBy('class_level');

        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($studentsByClass as $classKey => $students) {
            $sheet = $sheetIndex === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet($sheetIndex);

            $sheet->setTitle(substr(AcademyOptions::classLabel($classKey), 0, 30));

            $headers = ['Name', 'Gender', 'Phone', 'Section', 'Year', 'Monthly Fee', 'Enrollment Date', 'Status'];
            $sheet->fromArray($headers, null, 'A1');

            $row = 2;
            foreach ($students as $student) {
                $sheet->fromArray([
                    $student->name,
                    $student->gender,
                    $student->phone_number,
                    AcademyOptions::sectionLabel($student->section),
                    $student->academic_year,
                    $student->monthly_fee,
                    optional($student->enrollment_date)->format('Y-m-d'),
                    ucfirst($student->status),
                ], null, "A{$row}");
                $row++;
            }

            foreach (range('A', 'H') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $sheetIndex++;
        }

        if ($sheetIndex === 0) {
            $spreadsheet->getActiveSheet()->setTitle('Students');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        $fileName = 'students-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
