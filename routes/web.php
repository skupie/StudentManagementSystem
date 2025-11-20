<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentExportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/students', 'pages.students')->name('students.index');
    Route::get('/students/export/excel', StudentExportController::class)->middleware('role:admin')->name('students.export.excel');
    Route::view('/attendance', 'pages.attendance')->name('attendance.index');
    Route::view('/attendance-overview', 'pages.attendance-overview')->middleware('role:admin')->name('attendance.overview');
    Route::view('/fees', 'pages.fees')->name('fees.index');
    Route::view('/due-list', 'pages.due-list')->name('due-list.index');
    Route::view('/notes', 'pages.notes')->middleware('role:instructor')->name('notes.index');
    Route::view('/weekly-exams', 'pages.weekly-exams')->name('weekly-exams.index');
    Route::view('/ledger', 'pages.ledger')->middleware('role:admin')->name('ledger.index');
    Route::view('/reports', 'pages.reports')->name('reports.index');
    Route::view('/users', 'pages.users')->middleware('role:admin')->name('users.index');

    Route::get('/reports/weekly-exams/pdf', [ReportController::class, 'weeklyExams'])
        ->middleware('role:admin,instructor')
        ->name('reports.weekly-exams.pdf');
    Route::get('/reports/weekly-exams/student/pdf', [ReportController::class, 'weeklyExamsStudent'])
        ->middleware('role:admin,instructor')
        ->name('reports.weekly-exams.student.pdf');
    Route::get('/reports/weekly-exams/student/excel', [ReportController::class, 'weeklyExamsStudentExcel'])
        ->middleware('role:admin,instructor')
        ->name('reports.weekly-exams.student.excel');

    Route::get('/reports/due-list/pdf', [ReportController::class, 'dueList'])
        ->middleware('role:admin,instructor')
        ->name('reports.due-list.pdf');
    Route::get('/reports/due-list/excel', [ReportController::class, 'dueListExcel'])
        ->middleware('role:admin,instructor')
        ->name('reports.due-list.excel');
    Route::get('/reports/weekly-exams/excel', [ReportController::class, 'weeklyExamsExcel'])
        ->middleware('role:admin,instructor')
        ->name('reports.weekly-exams.excel');
    Route::get('/reports/finance/excel', [ReportController::class, 'financeExcel'])
        ->middleware('role:admin')
        ->name('reports.finance.excel');

    Route::get('/reports/finance/pdf', [ReportController::class, 'finance'])
        ->middleware('role:admin')
        ->name('reports.finance.pdf');
});
