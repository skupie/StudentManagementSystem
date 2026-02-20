<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentExportController;
use App\Http\Controllers\StudentNotesController;
use App\Http\Controllers\StudentPortalNotesController;
use App\Http\Controllers\TeacherNoteFileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

Route::redirect('/', '/login');
Route::view('/teacher/login', 'auth.teacher-login')->name('teacher.login');
Route::view('/student/login', 'auth.student-login')->name('student.login');

// Teacher portal on dedicated subdomain (if configured). Shows the teacher login without changing the URL path.
if ($teacherDomain = env('TEACHER_PORTAL_DOMAIN')) {
    Route::domain($teacherDomain)->group(function () {
        Route::view('/', 'auth.teacher-login')->name('teacher.domain.login');
    });
}
if ($studentDomain = env('STUDENT_PORTAL_DOMAIN')) {
    Route::domain($studentDomain)->group(function () {
        Route::view('/', 'auth.student-login')->name('student.domain.login');
    });
}

// Public published model test results (no auth required)
Route::view('/result', 'pages.public-model-test-results')->name('model-tests.publish.public');

// Public bcrypt helper (no auth required)
Route::get('/make-p', function (Request $request) {
    $plain = (string) $request->query('value', '');
    $hash = $plain !== '' ? Hash::make($plain) : null;

    return view('pages.make-password', [
        'input' => $plain,
        'hash' => $hash,
    ]);
})->name('make.password');

// Public routine viewer (no authentication required)
Route::view('/routine-schedule', 'pages.public-routines')->name('routines.public');
Route::get('/student-notes', StudentNotesController::class)->name('student.notes.public');
Route::get('/teacher-notes/file/{teacherNote}', TeacherNoteFileController::class)->name('teacher.notes.file');

// Public artisan runner (PIN protected)
Route::get('/artisan', [ArtisanController::class, 'show'])->name('artisan.page');
Route::post('/artisan', [ArtisanController::class, 'run'])->middleware('throttle:10,1')->name('artisan.run');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/teacher-portal', 'pages.teacher-portal')
        ->middleware('role:teacher,lead_instructor')
        ->name('teacher.portal');
    Route::view('/teacher-transactions', 'pages.teacher-transactions')
        ->middleware('role:teacher,lead_instructor')
        ->name('teacher.transactions');
    Route::view('/student-portal', 'pages.student-portal')
        ->middleware('role:student')
        ->name('student.portal');
    Route::view('/student-routines', 'pages.student-routines')
        ->middleware('role:student')
        ->name('student.routines');
    Route::view('/student-results', 'pages.student-results')
        ->middleware('role:student')
        ->name('student.results');
    Route::view('/student-payments', 'pages.student-payments')
        ->middleware('role:student')
        ->name('student.payments');
    Route::get('/student-notes-library', StudentPortalNotesController::class)
        ->middleware('role:student')
        ->name('student.notes');
    Route::view('/student-credentials', 'pages.student-credentials')
        ->middleware('role:admin,director,teacher,instructor,lead_instructor')
        ->name('students.credentials');
    Route::view('/weekly-exam-assignments', 'pages.weekly-exam-assignments')
        ->middleware('role:admin,director,assistant')
        ->name('weekly-exam-assignments.index');
    Route::view('/weekly-exam-syllabus', 'pages.weekly-exam-syllabus')
        ->middleware('role:admin,director,assistant,teacher,instructor,lead_instructor')
        ->name('weekly-exam-syllabus.index');
    Route::view('/class-notes', 'pages.class-notes')
        ->middleware('role:admin,director,instructor,teacher,lead_instructor')
        ->name('class.notes.index');
    Route::view('/students', 'pages.students')->middleware('role:admin,director,assistant,instructor')->name('students.index');
    Route::view('/student-notices', 'pages.student-notices')->middleware('role:admin,director,assistant,instructor')->name('students.notices');
    Route::get('/students/export/excel', StudentExportController::class)->middleware('role:admin,director,instructor')->name('students.export.excel');
    Route::view('/transfer', 'pages.transfer')->middleware('role:admin,director,instructor')->name('students.transfer');
    Route::view('/attendance', 'pages.attendance')->middleware('role:admin,director,assistant,instructor')->name('attendance.index');
    Route::view('/attendance-overview', 'pages.attendance-overview')->middleware('role:admin,director')->name('attendance.overview');
    Route::view('/teacher-payments', 'pages.teacher-payments')->middleware('role:admin,director,instructor')->name('teacher.payments');
    Route::view('/routines', 'pages.routines')->middleware('role:admin,director,assistant,teacher,instructor,lead_instructor')->name('routines.index');
    Route::view('/holidays', 'pages.holidays')->middleware('role:admin,director,assistant,instructor')->name('holidays.index');
    Route::view('/fees', 'pages.fees')->middleware('role:admin,director,assistant,instructor')->name('fees.index');
    Route::view('/due-list', 'pages.due-list')->middleware('role:admin,director,assistant,instructor')->name('due-list.index');
    Route::view('/notes', 'pages.notes')->middleware('role:assistant,instructor')->name('notes.index');
    Route::view('/weekly-exams', 'pages.weekly-exams')->middleware('role:admin,director,assistant,teacher,instructor,lead_instructor')->name('weekly-exams.index');
    Route::view('/ledger', 'pages.ledger')->middleware('role:admin')->name('ledger.index');
    Route::view('/reports', 'pages.reports')->middleware('role:admin,director,assistant,instructor')->name('reports.index');
    Route::view('/teachers', 'pages.teachers')->middleware('role:admin,director,assistant,instructor')->name('teachers.index');
    Route::view('/class-sections', 'pages.class-sections')->middleware('role:admin,director,instructor')->name('class.sections');
    Route::view('/audit-logs', 'pages.audit-logs')->middleware('role:admin,director')->name('audit.logs');
    Route::view('/model-tests', 'pages.model-tests')->middleware('role:admin,director,teacher,instructor,lead_instructor,assistant')->name('model-tests.index');
    Route::view('/model-test-results', 'pages.model-test-results')->middleware('role:admin,director,teacher,instructor,lead_instructor,assistant')->name('model-tests.results');
    Route::view('/leaderboard', 'pages.leaderboard')
        ->middleware('role:admin,director,assistant,instructor')
        ->name('leaderboard.index');
    Route::view('/users', 'pages.users')->middleware('role:admin')->name('users.index');
    Route::view('/management-entries', 'pages.management-entries')
        ->middleware('role:admin,director,instructor')
        ->name('management.entries');
    Route::get('/reports/attendance/pdf', [ReportController::class, 'attendancePdf'])
        ->middleware('role:admin,director')
        ->name('reports.attendance.pdf');
    Route::get('/reports/attendance/excel', [ReportController::class, 'attendanceExcel'])
        ->middleware('role:admin,director')
        ->name('reports.attendance.excel');
    Route::get('/reports/attendance/matrix/xlsx', [ReportController::class, 'attendanceMatrixXlsx'])
        ->middleware('role:admin,director,assistant,instructor')
        ->name('reports.attendance.matrix.xlsx');
    Route::get('/reports/attendance/matrix', [ReportController::class, 'attendanceMatrixCsv'])
        ->middleware('role:admin,director,assistant,instructor')
        ->name('reports.attendance.matrix.csv');

    Route::get('/reports/weekly-exams/pdf', [ReportController::class, 'weeklyExams'])
        ->middleware('role:admin,director,teacher,instructor,lead_instructor,assistant')
        ->name('reports.weekly-exams.pdf');
    Route::get('/reports/weekly-exams/student/pdf', [ReportController::class, 'weeklyExamsStudent'])
        ->middleware('role:admin,director,teacher,instructor,lead_instructor,assistant')
        ->name('reports.weekly-exams.student.pdf');
    Route::get('/reports/weekly-exams/student/excel', [ReportController::class, 'weeklyExamsStudentExcel'])
        ->middleware('role:admin,director,teacher,instructor,lead_instructor,assistant')
        ->name('reports.weekly-exams.student.excel');

    Route::get('/reports/due-list/pdf', [ReportController::class, 'dueList'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.due-list.pdf');
    Route::get('/reports/due-list/excel', [ReportController::class, 'dueListExcel'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.due-list.excel');
    Route::get('/reports/weekly-exams/excel', [ReportController::class, 'weeklyExamsExcel'])
        ->middleware('role:admin,director,teacher,instructor,lead_instructor,assistant')
        ->name('reports.weekly-exams.excel');
    Route::get('/reports/finance/excel', [ReportController::class, 'financeExcel'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.finance.excel');

    Route::get('/reports/finance/pdf', [ReportController::class, 'finance'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.finance.pdf');
});
