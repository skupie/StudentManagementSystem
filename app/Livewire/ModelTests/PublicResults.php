<?php

namespace App\Livewire\ModelTests;

use App\Models\ModelTest;
use App\Models\ModelTestResult;
use App\Models\ModelTestStudent;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class PublicResults extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sectionFilter = 'all';
    public string $year = '';
    public string $examFilter = 'all';
    public string $mobileInput = '';
    public string $hscBatchInput = '';
    public bool $verified = false;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->year = (string) now()->year;
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'sectionFilter', 'examFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        if (! Cache::get('model_tests_published', false)) {
            return view('livewire.model-tests.public-results', [
                'students' => ModelTestStudent::whereRaw('1=0')->paginate(15),
                'finals' => [],
                'sectionOptions' => $this->sectionOptions(),
                'examOptions' => ModelTest::orderBy('name')->get(['id', 'name']),
            ]);
        }

        if (! $this->verified) {
            return view('livewire.model-tests.public-results', [
                'students' => ModelTestStudent::whereRaw('1=0')->paginate(15),
                'finals' => [],
                'sectionOptions' => $this->sectionOptions(),
                'examOptions' => ModelTest::orderBy('name')->get(['id', 'name']),
            ]);
        }

        $students = ModelTestStudent::query()
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where('name', 'like', $term)->orWhere('contact_number', 'like', $term);
            })
            ->orderBy('name')
            ->paginate(15);

        $finals = [];
        foreach ($students as $student) {
            $finals[$student->id] = $this->finalGradeForStudent($student->id);
        }

        $exams = ModelTest::orderBy('name')->get(['id', 'name']);

        return view('livewire.model-tests.public-results', [
            'students' => $students,
            'finals' => $finals,
            'sectionOptions' => $this->sectionOptions(),
            'examOptions' => $exams,
        ]);
    }

    public function verifyMobile(): void
    {
        $this->resetErrorBag();
        $this->validate([
            'mobileInput' => ['required', 'string'],
            'hscBatchInput' => ['required', 'string'],
        ]);

        $mobile = trim($this->mobileInput);
        $batch = trim($this->hscBatchInput);
        $exists = ModelTestStudent::where('contact_number', $mobile)
            ->when($batch !== '', fn ($q) => $q->where('year', $batch))
            ->exists();

        if ($exists) {
            $this->verified = true;
            $this->year = $batch;
        } else {
            $this->addError('mobileInput', 'Invalid Student Record');
        }
    }

    protected function sectionOptions(): array
    {
        return [
            'science' => 'Science',
            'business_studies' => 'Business Studies',
            'humanities' => 'Humanities',
        ];
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
            $gradeData = ModelTestResult::gradeForScore($avgTotal);
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

    protected function normalizeSubjectKey(?string $subject): string
    {
        if (! $subject) {
            return '';
        }

        $key = strtolower(trim($subject));
        // Strip paper identifiers like 1st/2nd etc.
        return preg_replace('/[\\s_-]*(1st|2nd|3rd|4th)$/i', '', $key) ?? $key;
    }
}
