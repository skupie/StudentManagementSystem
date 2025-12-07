<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentExportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Public routine viewer (no authentication required)
Route::view('/routine-schedule', 'pages.public-routines')->name('routines.public');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/students', 'pages.students')->name('students.index');
    Route::get('/students/export/excel', StudentExportController::class)->middleware('role:admin,director')->name('students.export.excel');
    Route::view('/transfer', 'pages.transfer')->middleware('role:admin,director,lead_instructor,instructor')->name('students.transfer');
    Route::view('/attendance', 'pages.attendance')->name('attendance.index');
    Route::view('/attendance-overview', 'pages.attendance-overview')->middleware('role:admin,director')->name('attendance.overview');
    Route::view('/teacher-payments', 'pages.teacher-payments')->middleware('role:admin,director')->name('teacher.payments');
    Route::view('/routines', 'pages.routines')->name('routines.index');
    Route::view('/holidays', 'pages.holidays')->middleware('role:admin,director,assistant')->name('holidays.index');
    Route::view('/fees', 'pages.fees')->name('fees.index');
    Route::view('/due-list', 'pages.due-list')->name('due-list.index');
    Route::view('/notes', 'pages.notes')->middleware('role:instructor,assistant')->name('notes.index');
    Route::view('/weekly-exams', 'pages.weekly-exams')->name('weekly-exams.index');
    Route::view('/ledger', 'pages.ledger')->middleware('role:admin')->name('ledger.index');
    Route::view('/reports', 'pages.reports')->name('reports.index');
    Route::view('/teachers', 'pages.teachers')->name('teachers.index');
    Route::view('/leaderboard', 'pages.leaderboard')
        ->middleware('role:admin,director,instructor,assistant')
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
        ->middleware('role:admin,director,instructor,assistant')
        ->name('reports.attendance.matrix.xlsx');
    Route::get('/reports/attendance/matrix', [ReportController::class, 'attendanceMatrixCsv'])
        ->middleware('role:admin,director,instructor,assistant')
        ->name('reports.attendance.matrix.csv');

    Route::get('/reports/weekly-exams/pdf', [ReportController::class, 'weeklyExams'])
        ->middleware('role:admin,director,instructor,assistant')
        ->name('reports.weekly-exams.pdf');
    Route::get('/reports/weekly-exams/student/pdf', [ReportController::class, 'weeklyExamsStudent'])
        ->middleware('role:admin,director,instructor,assistant')
        ->name('reports.weekly-exams.student.pdf');
    Route::get('/reports/weekly-exams/student/excel', [ReportController::class, 'weeklyExamsStudentExcel'])
        ->middleware('role:admin,director,instructor,assistant')
        ->name('reports.weekly-exams.student.excel');

    Route::get('/reports/due-list/pdf', [ReportController::class, 'dueList'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.due-list.pdf');
    Route::get('/reports/due-list/excel', [ReportController::class, 'dueListExcel'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.due-list.excel');
    Route::get('/reports/weekly-exams/excel', [ReportController::class, 'weeklyExamsExcel'])
        ->middleware('role:admin,director,instructor,assistant')
        ->name('reports.weekly-exams.excel');
    Route::get('/reports/finance/excel', [ReportController::class, 'financeExcel'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.finance.excel');

    Route::get('/reports/finance/pdf', [ReportController::class, 'finance'])
        ->middleware('role:admin,director,instructor')
        ->name('reports.finance.pdf');
});
