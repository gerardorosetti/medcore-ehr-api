<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentNotificationJob;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\PatientProfile;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('doctor', 'web');
    Role::findOrCreate('patient', 'web');
});

it('calculates available 30-minute consultation slots for a doctor and excludes confirmed bookings', function (): void {
    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');

    $doctor = DoctorProfile::factory()->create([
        'user_id' => $doctorUser->id,
        'consultation_duration_minutes' => 30,
        'is_accepting_patients' => true,
    ]);

    // Choose next Tuesday to guarantee a forward-looking date
    $targetDate = CarbonImmutable::now()->next(Carbon::TUESDAY);

    DoctorSchedule::factory()->create([
        'doctor_profile_id' => $doctor->id,
        'day_of_week' => $targetDate->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '11:00:00',
        'is_active' => true,
    ]);

    // Book an existing confirmed appointment from 09:30 to 10:00
    Appointment::factory()->create([
        'doctor_profile_id' => $doctor->id,
        'status' => AppointmentStatus::Confirmed,
        'scheduled_at' => $targetDate->setTime(9, 30, 0),
        'ends_at' => $targetDate->setTime(10, 0, 0),
    ]);

    $response = $this->getJson("/api/v1/doctors/{$doctor->id}/available-slots?date={$targetDate->format('Y-m-d')}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'doctor_id',
                'date',
                'consultation_duration_minutes',
                'available_slots' => [
                    '*' => ['start_time', 'end_time', 'scheduled_at', 'duration_minutes'],
                ],
            ],
        ]);

    $availableSlots = $response->json('data.available_slots');
    $startTimes = array_column($availableSlots, 'start_time');

    // 09:00 to 11:00 with 30m slots gives 09:00, 09:30, 10:00, 10:30.
    // 09:30 is booked, so only 09:00, 10:00, 10:30 should be available.
    expect($availableSlots)->toHaveCount(3)
        ->and($startTimes)->toBe(['09:00', '10:00', '10:30'])
        ->and($startTimes)->not->toContain('09:30');
});

it('schedules appointments, sanitizes reason via pipe operator, pushes notification job, and logs audit', function (): void {
    Queue::fake();

    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctor = DoctorProfile::factory()->create([
        'user_id' => $doctorUser->id,
        'consultation_duration_minutes' => 30,
        'is_accepting_patients' => true,
    ]);

    $bookingDate = CarbonImmutable::now()->next(Carbon::WEDNESDAY);

    DoctorSchedule::factory()->create([
        'doctor_profile_id' => $doctor->id,
        'day_of_week' => $bookingDate->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $scheduledAt = $bookingDate->setTime(14, 0, 0);

    $payload = [
        'doctor_profile_id' => $doctor->id,
        'scheduled_at' => $scheduledAt->toIso8601String(),
        'reason' => '<script>alert(1)</script>Severe chest pain',
        'notes' => '<b>Urgent</b> evaluation requested',
    ];

    $response = $this->actingAs($patientUser)
        ->postJson('/api/v1/appointments', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.doctor.id', $doctor->id)
        ->assertJsonPath('data.patient.id', $patientProfile->id);

    $appointment = Appointment::query()->where('doctor_profile_id', $doctor->id)->firstOrFail();

    // Verify PHP 8.5 pipe operator sanitized HTML tags & normalized whitespace
    expect($appointment->reason)->toBe('Severe chest pain')
        ->and($appointment->notes)->toBe('Urgent evaluation requested');

    // Verify async notification job queued
    Queue::assertPushed(
        SendAppointmentNotificationJob::class,
        fn (SendAppointmentNotificationJob $job): bool => $job->appointmentId === $appointment->id
    );

    // Verify HIPAA compliance audit log entry
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'appointment.scheduled',
        'auditable_type' => Appointment::class,
        'auditable_id' => $appointment->id,
        'user_id' => $patientUser->id,
    ]);
});

it('prevents double-booking overlapping slots for the same doctor with 422 validation error', function (): void {
    $patientA = User::factory()->create();
    $patientA->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $patientA->id]);

    $patientB = User::factory()->create();
    $patientB->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $patientB->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctor = DoctorProfile::factory()->create([
        'user_id' => $doctorUser->id,
        'consultation_duration_minutes' => 30,
        'is_accepting_patients' => true,
    ]);

    $bookingDate = CarbonImmutable::now()->next(Carbon::THURSDAY);

    DoctorSchedule::factory()->create([
        'doctor_profile_id' => $doctor->id,
        'day_of_week' => $bookingDate->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $slotTime = $bookingDate->setTime(10, 0, 0);

    // Initial booking succeeds
    $this->actingAs($patientA, 'web')->postJson('/api/v1/appointments', [
        'doctor_profile_id' => $doctor->id,
        'scheduled_at' => $slotTime->toIso8601String(),
        'reason' => 'Initial consultation',
    ])->assertStatus(201);

    // Second overlapping booking attempt must be rejected
    $response = $this->actingAs($patientB, 'web')->postJson('/api/v1/appointments', [
        'doctor_profile_id' => $doctor->id,
        'scheduled_at' => $slotTime->toIso8601String(),
        'reason' => 'Conflicting consultation request',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['scheduled_at'])
        ->assertJsonFragment([
            'scheduled_at' => ['The requested time slot is already booked for this doctor.'],
        ]);
});

it('rejects appointment scheduling outside doctor active working hours with 422', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctor = DoctorProfile::factory()->create([
        'user_id' => $doctorUser->id,
        'consultation_duration_minutes' => 30,
        'is_accepting_patients' => true,
    ]);

    $bookingDate = CarbonImmutable::now()->next(Carbon::FRIDAY);

    // Doctor only works 09:00 to 12:00
    DoctorSchedule::factory()->create([
        'doctor_profile_id' => $doctor->id,
        'day_of_week' => $bookingDate->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);

    // Attempt booking at 19:00
    $outsideTime = $bookingDate->setTime(19, 0, 0);

    $response = $this->actingAs($patientUser)->postJson('/api/v1/appointments', [
        'doctor_profile_id' => $doctor->id,
        'scheduled_at' => $outsideTime->toIso8601String(),
        'reason' => 'Evening emergency consultation',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['scheduled_at'])
        ->assertJsonFragment([
            'scheduled_at' => ["The requested time falls outside the doctor's active working hours."],
        ]);
});

it('enforces column identifier allowlists to defend against SQL injection on query parameters', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    // SQL injection attempt on appointment sort column
    $this->actingAs($patientUser)
        ->getJson('/api/v1/appointments?sort_by=id;DROP+TABLE+users--')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sort_by']);

    // SQL injection attempt on doctor catalog sort column
    $this->getJson('/api/v1/doctors?sort_by=malicious_column')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sort_by']);
});
