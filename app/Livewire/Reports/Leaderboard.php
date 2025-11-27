<?php

namespace App\Livewire\Reports;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Carbon\Carbon;
use Livewire\Component;

class Leaderboard extends Component
{
    public function render()
    {
        $activeGroups = Student::query()
            ->select('class_level', 'section')
            ->where('status', 'active')
            ->whereNotNull('class_level')
            ->whereNotNull('section')
            ->distinct()
            ->orderBy('class_level')
            ->orderBy('section')
            ->get();

        $activeGroups = $activeGroups
            ->unique(fn ($g) => $g->class_level . '|' . $g->section)
            ->values();

        $attendanceStats = Attendance::query()
            ->selectRaw('students.class_level, students.section, students.name, students.id as student_id, COUNT(*) as total_present')
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->where('students.status', 'active')
            ->where('attendances.status', 'present')
            ->groupBy('students.id', 'students.name', 'students.class_level', 'students.section')
            ->get();

        $hasAttendance = $attendanceStats->isNotEmpty();

        $attendanceMap = $attendanceStats
            ->groupBy(fn ($row) => $row->class_level . '|' . $row->section)
            ->map(function ($rows) {
                $max = $rows->max('total_present');
                return $rows->filter(fn ($row) => (int) $row->total_present === (int) $max)
                    ->map(fn ($row) => [
                        'name' => $row->name,
                        'total' => (int) $row->total_present,
                    ])->values();
            });

        $absentIds = WeeklyExamMark::query()
            ->select('student_id')
            ->whereNotNull('remarks')
            ->whereRaw('LOWER(remarks) = ?', ['absent'])
            ->pluck('student_id')
            ->unique();

        $examQuery = WeeklyExamMark::query()
            ->selectRaw('students.class_level, students.section, students.name, students.id as student_id, SUM(weekly_exam_marks.marks_obtained) as total_obtained, SUM(weekly_exam_marks.max_marks) as total_max, COUNT(*) as exam_count')
            ->join('students', 'students.id', '=', 'weekly_exam_marks.student_id')
            ->where('students.status', 'active')
            ->where(function ($query) {
                $query->whereNull('weekly_exam_marks.remarks')
                    ->orWhereRaw('LOWER(weekly_exam_marks.remarks) != ?', ['absent']);
            })
            ->groupBy('students.id', 'students.name', 'students.class_level', 'students.section');

        if ($absentIds->isNotEmpty()) {
            $examQuery->whereNotIn('students.id', $absentIds);
        }

        $examStats = $examQuery->get()->filter(fn ($row) => (float) $row->total_max > 0);

        foreach ($examStats as $stat) {
            $stat->average_percent = round(($stat->total_obtained / $stat->total_max) * 100, 2);
        }

        $examMap = $examStats
            ->groupBy(fn ($row) => $row->class_level . '|' . $row->section)
            ->map(function ($rows) {
                $max = $rows->max('average_percent');
                return $rows->filter(fn ($row) => (float) $row->average_percent === (float) $max)
                    ->map(fn ($row) => [
                        'name' => $row->name,
                        'average' => $row->average_percent,
                        'exams' => (int) $row->exam_count,
                    ])->values();
            });

        $groups = $activeGroups->map(function ($group) use ($attendanceMap, $examMap) {
            $key = $group->class_level . '|' . $group->section;
            return [
                'class_label' => AcademyOptions::classLabel($group->class_level),
                'section_label' => AcademyOptions::sectionLabel($group->section),
                'attendance' => $attendanceMap->get($key, collect()),
                'exam' => $examMap->get($key, collect()),
            ];
        });

        return view('livewire.reports.leaderboard', [
            'groups' => $groups,
            'hasAttendance' => $hasAttendance,
        ]);
    }
}
