<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\SelfAssessmentController;

/*
|--------------------------------------------------------------------------
| Web Routes (Student-facing)
|--------------------------------------------------------------------------
*/

// Landing → Chat
Route::get('/', fn () => redirect()->route('chat.index'))->name('home');

// Public pages
Route::view('/privacy-policy', 'privacy-policy')->name('privacy.policy');

/*
|--------------------------------------------------------------------------
| Registration (guest only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated user area
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Chat
    Route::get('/chat',                 [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/new',             [ChatController::class, 'newChat'])->name('chat.new');
    Route::get('/chat/history',         [ChatController::class, 'history'])->name('chat.history');
    Route::post('/chat',                [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/view/{id}',       [ChatController::class, 'viewSession'])->name('chat.view');
    Route::delete('/chat/session/{id}', [ChatController::class, 'deleteSession'])->name('chat.deleteSession');
    Route::delete('/chat/bulk-delete',  [ChatController::class, 'bulkDelete'])->name('chat.bulkDelete');
    Route::post('/chat/activate/{id}',  [ChatController::class, 'activate'])->name('chat.activate');

    // Profile
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',      [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',   [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Appointments (student booking)
    Route::get('/appointment',                   [AppointmentController::class, 'index'])->name('appointment.index');
    Route::post('/appointment',                  [AppointmentController::class, 'store'])->name('appointment.store');
    Route::get('/appointment/slots/{counselor}', [AppointmentController::class, 'slots'])->name('appointment.slots');
    Route::get('/appointment/history',           [AppointmentController::class, 'history'])->name('appointment.history');
    Route::get('/appointment/view/{id}',         [AppointmentController::class, 'show'])->name('appointment.view');
    Route::patch('/appointment/{id}/cancel',     [AppointmentController::class, 'cancel'])->name('appointment.cancel');

    // Self-Assessment (first step)
    Route::middleware(['auth'])->group(function () {
    Route::get('/self-assessment',        [SelfAssessmentController::class,'create'])->name('self-assessment.create');
    Route::post('/self-assessment/store', [SelfAssessmentController::class,'store'])->name('self-assessment.store');
    Route::get('/self-assessment/skip',   [SelfAssessmentController::class,'skip'])->name('self-assessment.skip');
});

    // Feature toggle via signed link
    Route::get('/features/enable-appointment', [FeaturesController::class, 'enableAppointment'])
        ->name('features.enable_appointment')
        ->middleware('signed');
});

/*
|--------------------------------------------------------------------------
| Settings (if you require verification)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings',  [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| Auth scaffolding (login, logout, password reset, etc.)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
