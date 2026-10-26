<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\PatientProfile;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('doctor', 'web');
    Role::findOrCreate('patient', 'web');
});

it('enforces brute-force rate limiting on authentication endpoints (429 Too Many Requests)', function (): void {
    $email = 'brute.force@medcore.com';
    $password = 'WrongPass123!';

    // Send 5 failed login attempts; each must be rejected with 422 Unprocessable Entity
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // 6th consecutive attempt must be throttled with 429 Too Many Requests
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => $password,
    ]);

    $response->assertStatus(429);
});

it('enforces Sanctum token ability scopes on protected clinical routes (403 Forbidden)', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create([
        'user_id' => $doctorUser->id,
        'is_accepting_patients' => true,
    ]);

    // Issue a restricted Sanctum personal access token with ONLY 'appointments:read' ability
    $token = $patientUser->createToken('read-only-token', ['appointments:read'])->plainTextToken;

    // Verifies GET /api/v1/appointments with that token succeeds (200 OK)
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/appointments')
        ->assertOk();

    // Verifies POST /api/v1/appointments (which requires appointments:write) with that token is rejected with 403 Forbidden
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/appointments', [
            'doctor_profile_id' => $doctorProfile->id,
            'scheduled_at' => CarbonImmutable::now()->addDays(2)->setTime(10, 0, 0)->toIso8601String(),
            'reason' => 'Routine cardiology consultation request',
        ])
        ->assertForbidden();

    // Verifies GET /api/v1/medical-records (which requires medical-records:read) with that token is rejected with 403 Forbidden
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/medical-records')
        ->assertForbidden();
});

it('prevents invalid state transitions when cancelling already completed or cancelled appointments', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create(['user_id' => $doctorUser->id]);

    // Create an appointment that is already Completed
    $completedAppointment = Appointment::factory()->create([
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $doctorProfile->id,
        'status' => AppointmentStatus::Completed,
    ]);

    // Authenticate as the patient and attempt to cancel the completed appointment
    $response = $this->actingAs($patientUser)
        ->patchJson("/api/v1/appointments/{$completedAppointment->id}/cancel", [
            'cancellation_reason' => 'Changed my mind',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    // Also verify that an already Cancelled appointment rejects further cancellation transitions
    $cancelledAppointment = Appointment::factory()->create([
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $doctorProfile->id,
        'status' => AppointmentStatus::Cancelled,
    ]);

    $this->actingAs($patientUser)
        ->patchJson("/api/v1/appointments/{$cancelledAppointment->id}/cancel", [
            'cancellation_reason' => 'Duplicate cancellation attempt',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('prevents issuing duplicate medical records for the same appointment', function (): void {
    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create(['user_id' => $doctorUser->id]);

    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $appointment = Appointment::factory()->create([
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $doctorProfile->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $consultationPayload = [
        'diagnosis' => 'Acute bacterial pharyngitis with fever',
        'clinical_notes' => 'Patient presented with severe sore throat, fever of 38.5C, and tonsillar exudate. Rapid strep test confirmed positive.',
        'treatment_plan' => 'Oral Amoxicillin 500mg three times daily for 10 days. Rest and adequate hydration.',
        'vital_signs' => [
            'blood_pressure' => '120/80',
            'heart_rate_bpm' => 88,
            'temperature_c' => 38.5,
            'spo2_percent' => 98,
        ],
        'prescriptions' => [
            [
                'medication_name' => 'Amoxicillin',
                'dosage' => '500mg',
                'frequency' => 'Three times daily',
                'duration_days' => 10,
                'instructions' => 'Take with or after food',
            ],
        ],
    ];

    // Complete the consultation once -> 201 Created
    $this->actingAs($doctorUser)
        ->postJson("/api/v1/appointments/{$appointment->id}/medical-record", $consultationPayload)
        ->assertStatus(201);

    // Attempting a second consultation completion for the same appointment must fail with 422
    $secondAttemptResponse = $this->actingAs($doctorUser)
        ->postJson("/api/v1/appointments/{$appointment->id}/medical-record", $consultationPayload);

    $secondAttemptResponse->assertStatus(422)
        ->assertJsonValidationErrors(['appointment']);
});

it('returns empty slots and blocks booking when a doctor is not accepting patients', function (): void {
    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create([
        'user_id' => $doctorUser->id,
        'consultation_duration_minutes' => 30,
        'is_accepting_patients' => false,
    ]);

    $targetMonday = CarbonImmutable::now()->next(Carbon::MONDAY);

    DoctorSchedule::factory()->create([
        'doctor_profile_id' => $doctorProfile->id,
        'day_of_week' => $targetMonday->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    // Available slots endpoint returns an empty array []
    $slotsResponse = $this->getJson("/api/v1/doctors/{$doctorProfile->id}/available-slots?date={$targetMonday->format('Y-m-d')}");
    $slotsResponse->assertOk()
        ->assertJsonPath('data.available_slots', []);

    // Authenticate as a patient and attempt booking for next Monday at 10:00
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $scheduledAt = $targetMonday->setTime(10, 0, 0);

    $bookingResponse = $this->actingAs($patientUser)->postJson('/api/v1/appointments', [
        'doctor_profile_id' => $doctorProfile->id,
        'scheduled_at' => $scheduledAt->toIso8601String(),
        'reason' => 'Routine cardiology consultation request',
    ]);

    $bookingResponse->assertStatus(422)
        ->assertJsonValidationErrors(['doctor_profile_id']);
});

it('prevents a doctor from modifying another doctor weekly schedule (403 Forbidden) while allowing own schedule updates', function (): void {
    $doctorUserA = User::factory()->create();
    $doctorUserA->assignRole('doctor');
    $doctorProfileA = DoctorProfile::factory()->create(['user_id' => $doctorUserA->id]);

    $doctorUserB = User::factory()->create();
    $doctorUserB->assignRole('doctor');
    $doctorProfileB = DoctorProfile::factory()->create(['user_id' => $doctorUserB->id]);

    $schedulePayload = [
        'schedules' => [
            [
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
            ],
        ],
    ];

    // Doctor A attempts to modify Doctor B's weekly schedule -> 403 Forbidden
    $this->actingAs($doctorUserA)
        ->putJson("/api/v1/doctors/{$doctorProfileB->id}/schedules", $schedulePayload)
        ->assertForbidden();

    // Doctor A updates their own weekly schedule -> 200 OK
    $this->actingAs($doctorUserA)
        ->putJson("/api/v1/doctors/{$doctorProfileA->id}/schedules", $schedulePayload)
        ->assertOk();

    // Verify the schedule was successfully persisted in the database
    $this->assertDatabaseHas('doctor_schedules', [
        'doctor_profile_id' => $doctorProfileA->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);
});

it('enforces pagination upper bounds to prevent resource exhaustion attacks (OWASP API4)', function (): void {
    $response = $this->getJson('/api/v1/doctors?per_page=5000');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});
