<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\MedicalRecordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MedCore EHR API Routes (v1)
|--------------------------------------------------------------------------
|
| Versioned RESTful API endpoints for authentication, doctor schedule &
| slot availability discovery, appointment booking and cancellation,
| encrypted medical records, prescriptions, and HIPAA audit trails.
|
*/

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    // Public Authentication & Registration
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('api.v1.auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('api.v1.auth.register');

    // Public Doctor Directory & Slot Availability Engine
    Route::get('/doctors', [DoctorController::class, 'index'])->name('api.v1.doctors.index');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('api.v1.doctors.show');
    Route::get('/doctors/{doctor}/available-slots', [DoctorController::class, 'availableSlots'])->name('api.v1.doctors.available-slots');

    // Authenticated Clinical Endpoints (Sanctum Bearer Token)
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Doctor Schedule Management
        Route::put('/doctors/{doctor}/schedules', [DoctorController::class, 'upsertSchedules'])->name('api.v1.doctors.schedules.upsert');

        // Appointments Lifecycle
        Route::get('/appointments', [AppointmentController::class, 'index'])
            ->middleware('ability:appointments:read')
            ->name('api.v1.appointments.index');
        Route::post('/appointments', [AppointmentController::class, 'store'])
            ->middleware(['throttle:appointments', 'ability:appointments:write'])
            ->name('api.v1.appointments.store');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])
            ->middleware('ability:appointments:read')
            ->name('api.v1.appointments.show');
        Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
            ->middleware(['throttle:appointments', 'ability:appointments:write'])
            ->name('api.v1.appointments.cancel');

        // Electronic Health Records (EHR) & Prescriptions
        Route::post('/appointments/{appointment}/medical-record', [MedicalRecordController::class, 'storeForAppointment'])
            ->middleware('ability:medical-records:write,prescriptions:write')
            ->name('api.v1.appointments.medical-record.store');
        Route::get('/medical-records', [MedicalRecordController::class, 'index'])
            ->middleware('ability:medical-records:read')
            ->name('api.v1.medical-records.index');
        Route::get('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'show'])
            ->middleware('ability:medical-records:read')
            ->name('api.v1.medical-records.show');

        // HIPAA Compliance Audit Trail (Admin Only)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.v1.audit-logs.index');
    });
});
