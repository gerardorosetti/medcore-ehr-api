<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\PatientProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes & Clinical Command Console
|--------------------------------------------------------------------------
|
| Serves the live architecture status dashboard, system telemetry,
| pre-seeded demo accounts with one-click token acquisition, and
| JSON health monitoring endpoints.
|
*/

Route::get('/', function () {
    $dbConnected = false;
    $redisConnected = false;
    $counts = [
        'doctors' => 0,
        'patients' => 0,
        'appointments' => 0,
        'encrypted_records' => 0,
        'audit_logs' => 0,
    ];

    try {
        DB::connection()->getPdo();
        $dbConnected = true;

        $counts['doctors'] = DoctorProfile::count();
        $counts['patients'] = PatientProfile::count();
        $counts['appointments'] = Appointment::count();
        $counts['encrypted_records'] = MedicalRecord::count();
        $counts['audit_logs'] = AuditLog::count();
    } catch (Throwable) {
        $dbConnected = false;
    }

    try {
        Redis::connection()->ping();
        $redisConnected = true;
    } catch (Throwable) {
        $redisConnected = false;
    }

    return view('welcome', [
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'db_connected' => $dbConnected,
        'redis_connected' => $redisConnected,
        'counts' => $counts,
    ]);
})->name('home');

Route::get('/api/v1/health', function (): JsonResponse {
    $dbOk = false;
    $redisOk = false;

    try {
        DB::connection()->getPdo();
        $dbOk = true;
    } catch (Throwable) {
        $dbOk = false;
    }

    try {
        Redis::connection()->ping();
        $redisOk = true;
    } catch (Throwable) {
        $redisOk = false;
    }

    $allHealthy = $dbOk && $redisOk;

    return response()->json([
        'status' => $allHealthy ? 'healthy' : 'degraded',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
        'services' => [
            'database' => $dbOk ? 'connected' : 'disconnected',
            'redis' => $redisOk ? 'connected' : 'disconnected',
        ],
        'version' => [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'api' => 'v1.0.0',
        ],
        'compliance' => [
            'phi_encryption' => 'AES-256-CBC (Eloquent Encrypted Casts)',
            'hipaa_audit_trail' => 'Immutable audit_logs active',
        ],
    ], $allHealthy ? 200 : 503);
})->name('api.v1.health');
