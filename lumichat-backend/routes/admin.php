<?php

use Illuminate\Support\Facades\Route;

// Auth controller for admin login form (reuse your auth controller)
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Admin controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CounselorController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ChatbotSessionController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\SelfAssessmentController;
use App\Http\Controllers\Admin\DiagnosisReportController;
use App\Http\Controllers\Admin\CourseAnalyticsController;

/*
|--------------------------------------------------------------------------
| Public (guest) admin auth routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.post');
    });

/*
|--------------------------------------------------------------------------
| Protected admin routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        /* ========== DASHBOARD ========== */
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        /* ========== COUNSELORS ========== */
        Route::resource('counselors', CounselorController::class)
            ->parameters(['counselors' => 'counselor']);

        /* ========== STUDENTS (read-only) ========== */
        Route::resource('students', StudentController::class)
            ->only(['index', 'show'])
            ->parameters(['students' => 'student']);

        /* ========== CHATBOT SESSIONS (read-only) ========== */
        Route::resource('chatbot-sessions', ChatbotSessionController::class)
            ->only(['index', 'show'])
            ->parameters(['chatbot-sessions' => 'session']);

        // Actual name (because it's inside the admin group): admin.chatbot-sessions.calendar
        Route::get('chatbot-sessions/{session}/calendar',
            [ChatbotSessionController::class, 'calendarCounts']
        )->name('chatbot-sessions.calendar')
         ->whereNumber('session');

        /* ========== APPOINTMENTS (Admin) ========== */
        Route::get('/appointments', [AdminAppointmentController::class, 'index'])
            ->name('appointments.index');

        Route::get('/appointments/{appointment}', [AdminAppointmentController::class, 'show'])
            ->name('appointments.show')
            ->whereNumber('appointment');

        Route::patch('/appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus'])
            ->name('appointments.status')
            ->whereNumber('appointment');

        Route::patch('/appointments/{appointment}/final-note', [AdminAppointmentController::class, 'saveNote'])
            ->name('appointments.saveNote')
            ->whereNumber('appointment');

        /* NEW: save diagnosis report to tbl_diagnosis_reports */
        Route::patch('/appointments/{appointment}/report', [AdminAppointmentController::class, 'saveReport'])
            ->name('appointments.saveReport')
            ->whereNumber('appointment');

        /* ========== SELF-ASSESSMENTS (Controller-driven) ========== */
        Route::get('/self-assessments', [SelfAssessmentController::class, 'index'])
            ->name('self-assessments.index');

        Route::get('/self-assessments/{assessment}', [SelfAssessmentController::class, 'show'])
            ->name('self-assessments.show')
            ->whereNumber('assessment');

        Route::post('/self-assessments/{assessment}/feedback', [SelfAssessmentController::class, 'feedback'])
            ->name('self-assessments.feedback')
            ->whereNumber('assessment');

             /* ========== Diagnosis Reports ========== */
           Route::resource('diagnosis-reports', DiagnosisReportController::class)
            ->only(['index','show'])
            ->parameters(['diagnosis-reports' => 'report']);

      

      /* ========== COURSE ANALYTICS (Controller-driven) ========== */
    Route::resource('course-analytics', CourseAnalyticsController::class)
    ->only(['index', 'show'])
    ->parameters(['course-analytics' => 'course'])      // {course} is the ID
    ->whereNumber('course');                             // numeric id
    });
