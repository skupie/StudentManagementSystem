<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherNote;
use App\Models\User;
use App\Support\AcademyOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentPortalNotesController extends Controller
{
    protected array $subjectFallbackByUser = [];

    public function __invoke(Request $request)
    {
        $student = $this->resolveStudent();
        $subjectColumnExists = Schema::hasColumn('teacher_notes', 'subject');
        $selectedSubject = (string) $request->query('subject', 'all');

        if (! $student) {
            return view('pages.student-notes-portal', [
                'student' => null,
                'subjectOptions' => [],
                'selectedSubject' => 'all',
                'notes' => collect(),
                'subjectColumnExists' => $subjectColumnExists,
            ]);
        }

        $baseQuery = TeacherNote::query()
            ->with('uploader')
            ->where(function ($q) use ($student) {
                $q->where('class_level', $student->class_level)
                    ->orWhereJsonContains('target_classes', $student->class_level);
            })
            ->where(function ($q) use ($student) {
                $q->where('section', $student->section)
                    ->orWhereJsonContains('target_sections', $student->section);
            });

        $subjectOptions = ['all' => 'All'];
        if ($subjectColumnExists) {
            $availableSubjects = (clone $baseQuery)
                ->whereNotNull('subject')
                ->where('subject', '!=', '')
                ->distinct()
                ->orderBy('subject')
                ->pluck('subject')
                ->all();

            foreach ($availableSubjects as $subjectKey) {
                $subjectOptions[$subjectKey] = AcademyOptions::subjectLabel((string) $subjectKey);
            }
        }

        if (! array_key_exists($selectedSubject, $subjectOptions)) {
            $selectedSubject = 'all';
        }

        $notes = (clone $baseQuery)
            ->when($subjectColumnExists, function ($query) use ($selectedSubject) {
                if ($selectedSubject !== 'all') {
                    $query->where('subject', $selectedSubject);
                }
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $notes->getCollection()->transform(function (TeacherNote $note) {
            $note->display_subject = $this->displaySubjectForNote($note);
            return $note;
        });

        return view('pages.student-notes-portal', [
            'student' => $student,
            'subjectOptions' => $subjectOptions,
            'selectedSubject' => $selectedSubject,
            'notes' => $notes,
            'subjectColumnExists' => $subjectColumnExists,
        ]);
    }

    protected function resolveStudent(): ?Student
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (! empty($user->studentProfile)) {
            return $user->studentProfile;
        }

        if (! empty($user->contact_number)) {
            $student = Student::where('phone_number', $user->contact_number)->first();
            if ($student) {
                return $student;
            }
        }

        return null;
    }

    protected function displaySubjectForNote(TeacherNote $note): string
    {
        $subjectKey = (string) ($note->subject ?? '');
        if ($subjectKey !== '') {
            return AcademyOptions::subjectLabel($subjectKey);
        }

        $fallback = $this->subjectFromTeacherAssignment((int) $note->uploaded_by);
        if ($fallback !== null) {
            return AcademyOptions::subjectLabel($fallback);
        }

        return '';
    }

    protected function subjectFromTeacherAssignment(int $uploadedBy): ?string
    {
        if (array_key_exists($uploadedBy, $this->subjectFallbackByUser)) {
            return $this->subjectFallbackByUser[$uploadedBy];
        }

        $user = User::query()->select('id', 'name', 'contact_number')->find($uploadedBy);
        if (! $user) {
            return $this->subjectFallbackByUser[$uploadedBy] = null;
        }

        $teacher = null;
        if (Schema::hasColumn('teachers', 'user_id')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
        }
        if (! $teacher && ! empty($user->contact_number)) {
            $teacher = Teacher::where('contact_number', $user->contact_number)->first();
        }
        if (! $teacher && ! empty($user->name)) {
            $teacher = Teacher::where('name', $user->name)->first();
        }

        if (! $teacher) {
            return $this->subjectFallbackByUser[$uploadedBy] = null;
        }

        $subjects = collect($teacher->subjects ?? [])
            ->map(fn ($v) => (string) $v)
            ->filter()
            ->values();

        if ($subjects->isEmpty() && ! empty($teacher->subject)) {
            $subjects->push((string) $teacher->subject);
        }

        if ($subjects->isEmpty()) {
            return $this->subjectFallbackByUser[$uploadedBy] = null;
        }

        return $this->subjectFallbackByUser[$uploadedBy] = (string) $subjects->first();
    }
}
