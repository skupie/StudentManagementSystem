<?php

namespace App\Livewire\ModelTests;

use App\Models\ModelTestResult;
use App\Support\AcademyOptions;
use Livewire\Component;
use Livewire\WithPagination;

class ModelTestResults extends Component
{
    use WithPagination;

    public string $search = '';
    public string $year = '';
    public string $typeFilter = 'all';
    public string $sectionFilter = 'all';
    public string $subjectFilter = 'all';

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->year = (string) now()->year;
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'year', 'typeFilter', 'sectionFilter', 'subjectFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = $this->baseQuery()->orderByDesc('year')->orderByDesc('created_at');
        $results = $query->paginate(15);

        $failedStudentIds = $this->failedStudents();

        return view('livewire.model-tests.model-test-results', [
            'results' => $results,
            'subjectOptions' => $this->subjectOptions(),
            'sectionOptions' => $this->sectionOptions(),
            'finalGradeFailMap' => $failedStudentIds,
        ]);
    }

    public function exportCsv()
    {
        if (! $this->canManage()) {
            abort(403);
        }

        $filename = 'model-test-results-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $failedMap = $this->failedStudents();

        $callback = function () use ($failedMap) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'Student',
                'Contact',
                'Section',
                'Year',
                'Model Test',
                'Subject',
                'Type',
                'MCQ',
                'CQ',
                'Practical',
                'Total',
                'Grade',
                'Grade Point',
                'Final Grade',
            ]);

            $this->baseQuery()
                ->orderBy('model_test_student_id')
                ->orderBy('model_test_id')
                ->chunk(200, function ($chunk) use ($handle, $failedMap) {
                    foreach ($chunk as $row) {
                        $student = $row->student;
                        $test = $row->test;
                        $finalGrade = $failedMap[$row->model_test_student_id] ?? false ? 'F' : $row->grade;
                        $subjectLabel = $this->subjectLabel($row->subject ?: $test?->subject);

                        fputcsv($handle, [
                            $student?->name ?? '',
                            $student?->contact_number ?? '',
                            $student?->section ?? '',
                            $row->year,
                            $test?->name ?? '',
                            $subjectLabel,
                            $test?->type ?? '',
                            $row->mcq_mark,
                            $row->cq_mark,
                            $row->practical_mark,
                            $row->total_mark,
                            $row->grade,
                            $row->grade_point,
                            $finalGrade,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    protected function baseQuery()
    {
        return ModelTestResult::query()
            ->with(['student', 'test'])
            ->when($this->year !== '', fn ($q) => $q->where('year', $this->year))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->whereHas('test', fn ($t) => $t->where('type', $this->typeFilter)))
            ->when($this->subjectFilter !== 'all', fn ($q) => $q->whereHas('test', fn ($t) => $t->where('subject', $this->subjectFilter)))
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

    protected function failedStudents(): array
    {
        return ModelTestResult::query()
            ->when($this->year !== '', fn ($q) => $q->where('year', $this->year))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section', $this->sectionFilter)))
            ->where('grade', 'F')
            ->pluck('model_test_student_id')
            ->unique()
            ->mapWithKeys(fn ($id) => [$id => true])
            ->toArray();
    }

    protected function subjectOptions(): array
    {
        return AcademyOptions::subjects();
    }

    protected function sectionOptions(): array
    {
        return AcademyOptions::sections();
    }

    protected function subjectLabel(?string $key): string
    {
        if (! $key) {
            return '';
        }

        $options = $this->subjectOptions();

        return $options[$key] ?? $key;
    }

    protected function canManage(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'director', 'instructor', 'assistant'], true);
    }
}
