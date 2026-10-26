<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('doctor', 'web');
    Role::findOrCreate('patient', 'web');
});

it('enforces BOLA and IDOR boundaries preventing Patient A from inspecting Patient B data', function (): void {
    // Patient A setup
    $patientA = User::factory()->create();
    $patientA->assignRole('patient');
    $profileA = PatientProfile::factory()->create(['user_id' => $patientA->id]);

    // Patient B setup
    $patientB = User::factory()->create();
    $patientB->assignRole('patient');
    $profileB = PatientProfile::factory()->create(['user_id' => $patientB->id]);

    // Doctor setup
    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create(['user_id' => $doctorUser->id]);

    // Appointment and Medical Record belonging strictly to Patient B
    $appointmentB = Appointment::factory()->create([
        'patient_profile_id' => $profileB->id,
        'doctor_profile_id' => $doctorProfile->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $medicalRecordB = MedicalRecord::factory()->create([
        'appointment_id' => $appointmentB->id,
        'patient_profile_id' => $profileB->id,
        'doctor_profile_id' => $doctorProfile->id,
    ]);

    // Patient A attempts to access Patient B's appointment -> 403 Forbidden
    $this->actingAs($patientA)
        ->getJson("/api/v1/appointments/{$appointmentB->id}")
        ->assertForbidden();

    // Patient A attempts to access Patient B's medical record -> 403 Forbidden
    $this->actingAs($patientA)
        ->getJson("/api/v1/medical-records/{$medicalRecordB->id}")
        ->assertForbidden();
});

it('atomically completes consultations with encrypted PHI at rest and writes prescription records', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create(['user_id' => $doctorUser->id]);

    $appointment = Appointment::factory()->create([
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $doctorProfile->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $payload = [
        'diagnosis' => 'Acute coronary syndrome with ST-elevation myocardial infarction',
        'clinical_notes' => 'Patient presented with acute crushing sub-sternal chest pressure radiating to left arm. ECG demonstrates ST elevations in leads V1-V4.',
        'treatment_plan' => 'Immediate cardiac catheterization laboratory activation, dual antiplatelet therapy, and continuous telemetry monitoring.',
        'vital_signs' => [
            'blood_pressure' => '160/98',
            'heart_rate_bpm' => 104,
            'temperature_c' => 36.8,
            'spo2_percent' => 94,
        ],
        'prescriptions' => [
            [
                'medication_name' => 'Aspirin Chewable',
                'dosage' => '325mg',
                'frequency' => 'Once immediately',
                'duration_days' => 1,
                'instructions' => 'Chew thoroughly before swallowing',
            ],
            [
                'medication_name' => 'Ticagrelor',
                'dosage' => '180mg loading dose',
                'frequency' => 'Then 90mg twice daily',
                'duration_days' => 30,
                'instructions' => 'Take with water at regular intervals',
            ],
        ],
    ];

    $response = $this->actingAs($doctorUser)
        ->postJson("/api/v1/appointments/{$appointment->id}/medical-record", $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'appointment_id',
                'patient_profile_id',
                'doctor_profile_id',
                'diagnosis',
                'clinical_notes',
                'treatment_plan',
                'vital_signs',
                'prescriptions',
            ],
        ]);

    $recordId = (string) $response->json('data.id');

    // Assert appointment transitioned to Completed
    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);

    // Assert 2 prescriptions created in database and encrypted at rest
    $this->assertDatabaseCount('prescriptions', 2);
    $rawPrescriptions = DB::table('prescriptions')->where('medical_record_id', $recordId)->get();
    expect($rawPrescriptions)->toHaveCount(2)
        ->and($rawPrescriptions[0]->medication_name)->not->toContain('Aspirin')
        ->and($rawPrescriptions[0]->dosage)->not->toContain('325mg')
        ->and($rawPrescriptions[0]->instructions)->not->toContain('Chew thoroughly');

    // Assert raw DB ciphertext at rest (AES-256 Eloquent encrypted cast including vital_signs)
    $rawRecord = DB::table('medical_records')->where('id', $recordId)->first();
    expect($rawRecord)->not->toBeNull()
        ->and($rawRecord->diagnosis)->not->toContain('Acute coronary syndrome')
        ->and($rawRecord->clinical_notes)->not->toContain('crushing sub-sternal')
        ->and($rawRecord->treatment_plan)->not->toContain('catheterization')
        ->and((string) $rawRecord->vital_signs)->not->toContain('165/95');

    // Assert transparent Eloquent model decryption for both MedicalRecord and Prescriptions
    $decryptedRecord = MedicalRecord::query()->with('prescriptions')->findOrFail($recordId);
    expect($decryptedRecord->diagnosis)->toBe('Acute coronary syndrome with ST-elevation myocardial infarction')
        ->and($decryptedRecord->clinical_notes)->toBe($payload['clinical_notes'])
        ->and($decryptedRecord->treatment_plan)->toBe($payload['treatment_plan'])
        ->and($decryptedRecord->vital_signs)->toBe($payload['vital_signs'])
        ->and($decryptedRecord->prescriptions->pluck('medication_name')->all())->toBe(['Aspirin Chewable', 'Ticagrelor']);
});

it('rejects unauthorized clinical consultation recording by unassigned doctors or patients with 403', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $assignedDoctorUser = User::factory()->create();
    $assignedDoctorUser->assignRole('doctor');
    $assignedDoctorProfile = DoctorProfile::factory()->create(['user_id' => $assignedDoctorUser->id]);

    $unassignedDoctorUser = User::factory()->create();
    $unassignedDoctorUser->assignRole('doctor');
    DoctorProfile::factory()->create(['user_id' => $unassignedDoctorUser->id]);

    $appointment = Appointment::factory()->create([
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $assignedDoctorProfile->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $consultationPayload = [
        'diagnosis' => 'Essential hypertension, uncomplicated',
        'clinical_notes' => 'Patient blood pressure monitored over two weeks.',
    ];

    // Unassigned doctor cannot record consultation
    $this->actingAs($unassignedDoctorUser, 'web')
        ->postJson("/api/v1/appointments/{$appointment->id}/medical-record", $consultationPayload)
        ->assertForbidden();

    // Patient cannot author consultation
    $this->actingAs($patientUser, 'web')
        ->postJson("/api/v1/appointments/{$appointment->id}/medical-record", $consultationPayload)
        ->assertForbidden();
});

it('audits authorized PHI inspection and restricts HIPAA compliance audit logs to administrators', function (): void {
    $patientUser = User::factory()->create();
    $patientUser->assignRole('patient');
    $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);

    $doctorUser = User::factory()->create();
    $doctorUser->assignRole('doctor');
    $doctorProfile = DoctorProfile::factory()->create(['user_id' => $doctorUser->id]);

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $appointment = Appointment::factory()->create([
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $doctorProfile->id,
        'status' => AppointmentStatus::Completed,
    ]);

    $medicalRecord = MedicalRecord::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_profile_id' => $patientProfile->id,
        'doctor_profile_id' => $doctorProfile->id,
    ]);

    // Authorized viewing of PHI by patient owner (single + index listings for both EHR and Appointments)
    $this->actingAs($patientUser, 'web')
        ->getJson("/api/v1/medical-records/{$medicalRecord->id}")
        ->assertOk();

    $this->actingAs($patientUser, 'web')
        ->getJson('/api/v1/medical-records')
        ->assertOk();

    $this->actingAs($patientUser, 'web')
        ->getJson("/api/v1/appointments/{$appointment->id}")
        ->assertOk();

    $this->actingAs($patientUser, 'web')
        ->getJson('/api/v1/appointments')
        ->assertOk();

    // Verify HIPAA audit trail entries recorded for every PHI read & listing endpoint
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $patientUser->id,
        'action' => 'medical_record.viewed_phi',
        'auditable_type' => MedicalRecord::class,
        'auditable_id' => $medicalRecord->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $patientUser->id,
        'action' => 'medical_record.listed_phi',
        'auditable_type' => User::class,
        'auditable_id' => $patientUser->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $patientUser->id,
        'action' => 'appointment.viewed_phi',
        'auditable_type' => Appointment::class,
        'auditable_id' => $appointment->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $patientUser->id,
        'action' => 'appointment.listed_phi',
        'auditable_type' => User::class,
        'auditable_id' => $patientUser->id,
    ]);

    // Audit logs access control verification
    // Patient -> 403 Forbidden
    $this->actingAs($patientUser)
        ->getJson('/api/v1/audit-logs')
        ->assertForbidden();

    // Doctor -> 403 Forbidden
    $this->actingAs($doctorUser)
        ->getJson('/api/v1/audit-logs')
        ->assertForbidden();

    // Admin -> 200 OK
    $this->actingAs($adminUser)
        ->getJson('/api/v1/audit-logs')
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});
