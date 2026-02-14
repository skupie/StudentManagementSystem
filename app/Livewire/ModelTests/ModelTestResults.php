<?php

namespace App\Livewire\ModelTests;

use App\Models\ModelTest;
use App\Models\ModelTestResult;
use App\Models\ModelTestStudent;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Livewire\Component;
use Livewire\WithPagination;

class ModelTestResults extends Component
{
    use WithPagination;

    public string $search = '';
    public string $year = '';
    public string $studentFilter = '';
    public string $examFilter = 'all';
    public string $sectionFilter = 'all';
    public string $subjectFilter = 'all';
    public bool $isPublished = false;
    public bool $showPublishModal = false;
    public bool $showUnpublishModal = false;
    public string $publishYear = '';
    public string $publishExam = '';
    public string $unpublishYear = '';
    public string $unpublishExam = '';

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->year = (string) now()->year;
        $this->isPublished = ! empty(Cache::get('model_tests_published_list', []));
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'year', 'studentFilter', 'sectionFilter', 'subjectFilter', 'examFilter'], true)) {
            $this->resetPage();
        }

        if ($field === 'sectionFilter') {
            $this->studentFilter = '';
        }
    }

    public function render()
    {
        $query = $this->baseQuery()->orderByDesc('year')->orderByDesc('created_at');
        $results = $query->paginate(15);

        $selectedStudent = $this->selectedStudent();
        $final = $selectedStudent ? $this->finalGradeForStudent($selectedStudent->id) : ['grade' => null, 'point' => null];

        return view('livewire.model-tests.model-test-results', [
            'results' => $results,
            'subjectOptions' => $this->subjectOptions(),
            'sectionOptions' => $this->sectionOptions(),
            'examOptions' => $this->examOptions(),
            'yearOptions' => $this->yearOptions(),
            'students' => $this->students(),
            'selectedStudent' => $selectedStudent,
            'finalGrade' => $final['grade'],
            'finalGradePoint' => $final['point'],
        ]);
    }

    public function exportXlsx()
    {
        if (! $this->canPublishResults()) {
            abort(403);
        }

        $spreadsheet = new Spreadsheet();
        $baseSheet = $spreadsheet->getActiveSheet();
        $baseSheet->setTitle('Report');

        $headers = [
            'Model Test',
            'Subject',
            'Section',
            'MCQ',
            'CQ',
            'Practical',
            'Total',
            'Grade',
            'GPA',
        ];

        $students = $this->students();
        $sheetIndex = 0;
        $hasData = false;

        foreach ($students as $student) {
            $results = $this->resultsForStudent($student->id);
            if ($results->isEmpty()) {
                continue;
            }

            $sheet = $sheetIndex === 0 ? $baseSheet : $spreadsheet->createSheet();
            $sheetIndex++;
            $sheet->setTitle(Str::limit($student->name ?: 'Student ' . $student->id, 31, '...'));

            $sheet->fromArray($headers, null, 'A1');
            $headerStyle = $sheet->getStyle('A1:I1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE5E7EB');

            $rowIndex = 2;
            $finalGradeData = $this->finalGradeForStudent($student->id);

            // Group multi-paper subjects to single row with averaged totals/points
            $grouped = [];
            foreach ($results as $row) {
                $key = $this->normalizeSubjectKey($row->subject ?? $row->test?->subject ?? '');
                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'rows' => [],
                        'subject_label' => $this->subjectLabel($row->subject ?: $row->test?->subject),
                    ];
                }
                $grouped[$key]['rows'][] = $row;
            }

            foreach ($grouped as $group) {
                $rows = collect($group['rows']);
                $count = $rows->count();
                $totalSum = $rows->sum('total_mark');
                $totalAvg = $count > 0 ? $totalSum / $count : 0;
                $totalMax = $rows->sum(function ($row) {
                    return (float) ($row->mcq_max ?? 0) + (float) ($row->cq_max ?? 0) + (float) ($row->practical_max ?? 0);
                });
                $isMcqOnly = $rows->every(fn ($r) => ($r->test?->type ?? 'full') === 'mcq');

                if ($isMcqOnly && $totalMax > 0 && $totalMax < 100) {
                    $percent = ($totalSum / $totalMax) * 100;
                    $gradeData = ModelTestResult::gradeForScore($percent);
                } else {
                    $gradeData = ModelTestResult::gradeForScore($totalAvg);
                }
                $grade = $gradeData['grade'];
                $pointValue = $gradeData['point'];

                $first = $rows->first();
                $test = $first?->test;

                $data = [
                    $test?->name ?? '',
                    $group['subject_label'] ?? ($first?->subject ?? ''),
                    $student?->section ?? '',
                    '', // MCQ hidden in summary
                    '', // CQ hidden
                    '', // Practical hidden
                    $totalAvg,
                    $grade,
                    $pointValue,
                ];

                $sheet->fromArray($data, null, 'A' . $rowIndex);

                // Style F grades red.
                $gradeCell = 'H' . $rowIndex;
                if ($grade === 'F') {
                    $sheet->getStyle($gradeCell)->getFont()->getColor()->setARGB(Color::COLOR_RED);
                }

                $rowIndex++;
            }

            // Final grade summary row similar to page footer.
            $summaryRow = $rowIndex + 1;
            $sheet->setCellValue('A' . $summaryRow, 'Final Grade');
            $sheet->setCellValue('H' . $summaryRow, $finalGradeData['grade'] ?? '—');
            $sheet->setCellValue('I' . $summaryRow, $finalGradeData['point'] !== null ? round($finalGradeData['point'], 2) : '—');

            if (($finalGradeData['grade'] ?? null) === 'F') {
                $sheet->getStyle('H' . $summaryRow)->getFont()->getColor()->setARGB(Color::COLOR_RED);
            }

            $hasData = true;
        }

        if (! $hasData) {
            $baseSheet->setTitle('No Results');
            $baseSheet->setCellValue('A1', 'No results to export.');
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'model-test-results-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function publishXlsx()
    {
        if (! $this->canManage()) {
            abort(403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Published Grades');

        $headers = ['Student', 'Section', 'Final Grade', 'GPA'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $students = $this->studentFilter !== ''
            ? ModelTestStudent::whereKey($this->studentFilter)->get()
            : $this->students();

        $row = 2;
        foreach ($students as $student) {
            $final = $this->finalGradeForStudent($student->id);
            if ($final['grade'] === null) {
                continue;
            }

            $sheet->fromArray([
                $student->name,
                $student->section,
                $final['grade'],
                $final['point'] !== null ? round($final['point'], 2) : '',
            ], null, 'A' . $row);

            if ($final['grade'] === 'F') {
                $sheet->getStyle('C' . $row)->getFont()->getColor()->setARGB(Color::COLOR_RED);
            }

            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'published-grades-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function publishPublic(bool $redirect = false)
    {
        if (! $this->canPublishResults()) {
            abort(403);
        }

        $this->showPublishModal = true;
        $this->publishYear = $this->year ?: (string) now()->year;
        $this->publishExam = '';
    }

    public function unpublishPublic(): void
    {
        if (! $this->canPublishResults()) {
            abort(403);
        }
        $this->showUnpublishModal = true;
        $this->unpublishYear = $this->year ?: (string) now()->year;
        $this->unpublishExam = '';
    }

    public function confirmPublish(): void
    {
        if (! $this->canPublishResults()) {
            abort(403);
        }

        $this->validate([
            'publishYear' => ['required', 'integer', 'min:2000', 'max:2100'],
            'publishExam' => ['required', 'exists:model_tests,id'],
        ]);

        $list = Cache::get('model_tests_published_list', []);
        $entry = ['exam_id' => (int) $this->publishExam, 'year' => (int) $this->publishYear];
        $list = collect($list)
            ->reject(fn ($item) => (int) $item['exam_id'] === $entry['exam_id'] && (int) $item['year'] === $entry['year'])
            ->push($entry)
            ->values()
            ->toArray();

        Cache::forever('model_tests_published_list', $list);
        $this->isPublished = true;
        $this->showPublishModal = false;
        $this->publishExam = '';
    }

    public function confirmUnpublish(): void
    {
        if (! $this->canPublishResults()) {
            abort(403);
        }

        $this->validate([
            'unpublishYear' => ['required', 'integer', 'min:2000', 'max:2100'],
            'unpublishExam' => ['required', 'exists:model_tests,id'],
        ]);

        $list = Cache::get('model_tests_published_list', []);
        $list = collect($list)
            ->reject(fn ($item) => (int) $item['exam_id'] === (int) $this->unpublishExam && (int) $item['year'] === (int) $this->unpublishYear)
            ->values()
            ->toArray();

        Cache::forever('model_tests_published_list', $list);
        $this->isPublished = ! empty($list);
        $this->showUnpublishModal = false;
        $this->unpublishExam = '';
    }

    protected function baseQuery()
    {
        return ModelTestResult::query()
            ->with(['student', 'test'])
            ->when($this->year !== '', fn ($q) => $q->where('year', $this->year))
            ->when($this->examFilter !== 'all', fn ($q) => $q->where('model_test_id', $this->examFilter))
            ->when($this->studentFilter !== '', fn ($q) => $q->where('model_test_student_id', $this->studentFilter))
            ->when($this->subjectFilter !== 'all', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('subject', $this->subjectFilter)
                        ->orWhereHas('test', fn ($t) => $t->where('subject', $this->subjectFilter));
                });
            })
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section', $this->sectionFilter)))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->whereHas('student', function ($sq) use ($term) {
                        $sq->where('name', 'like', $term)
                            ->orWhere('contact_number', 'like', $term);
                    })->orWhereHas('test', function ($tq) use ($term) {
                        $tq->where('name', 'like', $term)
                            ->orWhere('subject', 'like', $term);
                    });
                });
            });
    }

    protected function selectedStudent(): ?ModelTestStudent
    {
        if ($this->studentFilter === '') {
            return null;
        }

        return ModelTestStudent::find($this->studentFilter);
    }

    protected function finalGradeForStudent(int $studentId): array
    {
        $results = ModelTestResult::query()
            ->where('model_test_student_id', $studentId)
            ->when($this->year !== '', fn ($q) => $q->where('year', $this->year))
            ->when($this->examFilter !== 'all', fn ($q) => $q->where('model_test_id', $this->examFilter))
            ->get();

        if ($results->isEmpty()) {
            return ['grade' => null, 'point' => null];
        }

        $grouped = [];
        foreach ($results as $row) {
            $subjectKey = $this->normalizeSubjectKey($row->subject ?? $row->test?->subject ?? '');
            $grouped[$subjectKey][] = $row;
        }

        $mainSubjects = [];
        $optionalSubjects = [];

        foreach ($grouped as $subject => $rows) {
            $totalSum = collect($rows)->sum('total_mark');
            $count = count($rows);
            $avgTotal = $count > 0 ? $totalSum / $count : 0;
            $totalMax = collect($rows)->sum(function ($row) {
                return (float) ($row->mcq_max ?? 0) + (float) ($row->cq_max ?? 0) + (float) ($row->practical_max ?? 0);
            });
            $isMcqOnly = collect($rows)->every(fn ($r) => ($r->test?->type ?? 'full') === 'mcq');

            if ($isMcqOnly && $totalMax > 0 && $totalMax < 100) {
                $percent = ($totalSum / $totalMax) * 100;
                $gradeData = ModelTestResult::gradeForScore($percent);
            } else {
                $gradeData = ModelTestResult::gradeForScore($avgTotal);
            }
            $grade = $gradeData['grade'];
            $pointValue = $gradeData['point'];

            $isOptional = collect($rows)->contains(fn ($r) => (bool) $r->optional_subject);
            $bucket = [
                'grade' => $grade,
                'point' => $pointValue,
            ];
            if ($isOptional) {
                $optionalSubjects[] = $bucket;
            } else {
                $mainSubjects[] = $bucket;
            }
        }

        $mainCount = 0;
        $mainPoints = 0.0;
        $hasFailMain = false;

        foreach ($mainSubjects as $sub) {
            if ($sub['grade'] === null) {
                continue;
            }
            $mainCount++;
            if ($sub['grade'] === 'F') {
                $hasFailMain = true;
            }
            $mainPoints += $sub['point'] ?? 0.0;
        }

        // Optional rule: take the best adjusted bonus, not summed
        $optionalBonus = collect($optionalSubjects)
            ->map(function ($sub) {
                $grade = $sub['grade'];
                return match ($grade) {
                    'A+' => 3.00,
                    'A' => 2.00,
                    'A-' => 1.50,
                    'B' => 1.00,
                    default => 0.0,
                };
            })
            ->max() ?? 0.0;

        if ($mainCount === 0) {
            return ['grade' => null, 'point' => null];
        }

        $gpa = ($mainPoints + $optionalBonus) / $mainCount;
        $gpa = round($gpa, 2);

        if ($hasFailMain) {
            return ['grade' => 'F', 'point' => $gpa];
        }

        return [
            'grade' => $this->gradeFromPoint($gpa),
            'point' => $gpa,
        ];
    }

    protected function gradeFromPoint(float $point): string
    {
        return match (true) {
            $point >= 5.0 => 'A+',
            $point >= 4.0 => 'A',
            $point >= 3.5 => 'A-',
            $point >= 3.0 => 'B',
            $point >= 2.0 => 'C',
            $point >= 1.0 => 'D',
            default => 'F',
        };
    }

    protected function subjectOptions(): array
    {
        return AcademyOptions::subjects();
    }

    protected function sectionOptions(): array
    {
        return AcademyOptions::sections();
    }

    protected function examOptions()
    {
        return ModelTest::orderBy('name')->get(['id', 'name']);
    }

    protected function yearOptions()
    {
        return ModelTestResult::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (string) $year)
            ->toArray();
    }

    protected function normalizeSubjectKey(?string $subject): string
    {
        if (! $subject) {
            return '';
        }

        $key = strtolower(trim($subject));
        // Strip paper identifiers like 1st/2nd etc.
        $key = preg_replace('/[\\s_-]*(1st|2nd|3rd|4th)$/i', '', $key) ?? $key;

        return $key;
    }

    protected function subjectLabel(?string $key): string
    {
        if (! $key) {
            return '';
        }

        $options = $this->subjectOptions();
        $normalized = $this->normalizeSubjectKey($key);

        if (isset($options[$normalized])) {
            return $options[$normalized];
        }

        return $normalized !== '' ? ucfirst($normalized) : $key;
    }

    protected function canManage(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'director', 'instructor', 'assistant'], true);
    }

    protected function canPublishResults(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'director', 'assistant'], true);
    }

    protected function students()
    {
        return ModelTestStudent::query()
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->orderBy('name')
            ->get();
    }

    protected function resultsForStudent(int $studentId)
    {
        return ModelTestResult::query()
            ->with(['student', 'test'])
            ->where('model_test_student_id', $studentId)
            ->when($this->year !== '', fn ($q) => $q->where('year', $this->year))
            ->when($this->examFilter !== 'all', fn ($q) => $q->where('model_test_id', $this->examFilter))
            ->when($this->subjectFilter !== 'all', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('subject', $this->subjectFilter)
                        ->orWhereHas('test', fn ($t) => $t->where('subject', $this->subjectFilter));
                });
            })
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get();
    }
}

