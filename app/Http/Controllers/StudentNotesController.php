<?php

namespace App\Http\Controllers;

use App\Models\TeacherNote;
use App\Support\AcademyOptions;
use Illuminate\Http\Request;

class StudentNotesController extends Controller
{
    public function __invoke(Request $request)
    {
        $class = (string) $request->query('class', '');
        $section = (string) $request->query('section', '');

        $classOptions = AcademyOptions::classes();
        $sectionOptions = AcademyOptions::sections();

        $notes = TeacherNote::query()
            ->when($class !== '' && array_key_exists($class, $classOptions), function ($q) use ($class) {
                $q->where(function ($inner) use ($class) {
                    $inner->where('class_level', $class)
                        ->orWhereJsonContains('target_classes', $class);
                });
            })
            ->when($section !== '' && array_key_exists($section, $sectionOptions), function ($q) use ($section) {
                $q->where(function ($inner) use ($section) {
                    $inner->where('section', $section)
                        ->orWhereJsonContains('target_sections', $section);
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('pages.student-notes', [
            'notes' => $notes,
            'class' => $class,
            'section' => $section,
            'classOptions' => $classOptions,
            'sectionOptions' => $sectionOptions,
        ]);
    }
}
