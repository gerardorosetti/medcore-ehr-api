<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\BloodType;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Class DatabaseSeeder
 *
 * Populates the MedCore EHR database with an idempotent clinical demonstration dataset,
 * including RBAC roles, administrative users, licensed physicians with weekly schedules,
 * patients with HIPAA-encrypted medical histories, consultations, and audit logs.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Spatie RBAC Roles for guard 'web'
        $adminRole = Role::findOrCreate('admin', 'web');
        $doctorRole = Role::findOrCreate('doctor', 'web');
        $patientRole = Role::findOrCreate('patient', 'web');

        // 2. Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@medcore.com'],
            [
                'name' => 'System Administrator',
                'password' => 'Password123!',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles([$adminRole]);

        // 3. Doctor 1: Dr. Gregory House
        $drHouseUser = User::updateOrCreate(
            ['email' => 'dr.house@medcore.com'],
            [
                'name' => 'Dr. Gregory House',
                'password' => 'Password123!',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $drHouseUser->syncRoles([$doctorRole]);

        $drHouseProfile = DoctorProfile::updateOrCreate(
            ['user_id' => $drHouseUser->id],
            [
                'specialty' => 'Diagnostic Medicine',
                'license_number' => 'MED-100421',
                'phone' => '+1-555-0101',
                'bio' => 'Head of Diagnostic Medicine, board certified in Infectious Disease and Nephrology.',
                'consultation_duration_minutes' => 30,
                'is_accepting_patients' => true,
            ]
        );

        $houseDays = [
            $this->resolveDayOfWeek(1, 'Monday'),
            $this->resolveDayOfWeek(2, 'Tuesday'),
            $this->resolveDayOfWeek(3, 'Wednesday'),
            $this->resolveDayOfWeek(4, 'Thursday'),
            $this->resolveDayOfWeek(5, 'Friday'),
        ];

        foreach ($houseDays as $day) {
            DoctorSchedule::updateOrCreate(
                [
                    'doctor_profile_id' => $drHouseProfile->id,
                    'day_of_week' => $day,
                ],
                [
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'is_active' => true,
                ]
            );
        }

        // 4. Doctor 2: Dr. Lisa Cuddy
        $drCuddyUser = User::updateOrCreate(
            ['email' => 'dr.cuddy@medcore.com'],
            [
                'name' => 'Dr. Lisa Cuddy',
                'password' => 'Password123!',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $drCuddyUser->syncRoles([$doctorRole]);

        $drCuddyProfile = DoctorProfile::updateOrCreate(
            ['user_id' => $drCuddyUser->id],
            [
                'specialty' => 'Endocrinology',
                'license_number' => 'MED-100892',
                'phone' => '+1-555-0102',
                'bio' => 'Dean of Medicine and Director of Endocrinology Services.',
                'consultation_duration_minutes' => 30,
                'is_accepting_patients' => true,
            ]
        );

        $cuddyDays = [
            $this->resolveDayOfWeek(1, 'Monday'),
            $this->resolveDayOfWeek(2, 'Tuesday'),
            $this->resolveDayOfWeek(3, 'Wednesday'),
            $this->resolveDayOfWeek(4, 'Thursday'),
            $this->resolveDayOfWeek(5, 'Friday'),
        ];

        foreach ($cuddyDays as $day) {
            DoctorSchedule::updateOrCreate(
                [
                    'doctor_profile_id' => $drCuddyProfile->id,
                    'day_of_week' => $day,
                ],
                [
                    'start_time' => '08:00:00',
                    'end_time' => '16:00:00',
                    'is_active' => true,
                ]
            );
        }

        // 5. Patient 1: Ana Martinez (O+, allergies Penicillin, Latex)
        $patientAnaUser = User::updateOrCreate(
            ['email' => 'patient.ana@medcore.com'],
            [
                'name' => 'Ana Martinez',
                'password' => 'Password123!',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $patientAnaUser->syncRoles([$patientRole]);

        $patientAnaProfile = PatientProfile::updateOrCreate(
            ['user_id' => $patientAnaUser->id],
            [
                'date_of_birth' => '1990-05-14',
                'gender' => 'female',
                'blood_type' => $this->resolveBloodType('O+', 'OPositive'),
                'phone' => '+1-555-0201',
                'allergies' => ['Penicillin', 'Latex'],
                'chronic_conditions' => 'Mild seasonal asthma',
                'emergency_contact_name' => 'Mateo Martinez',
                'emergency_contact_phone' => '+1-555-0202',
            ]
        );

        // 6. Patient 2: Carlos Mendoza (A+, allergies Sulfa drugs)
        $patientCarlosUser = User::updateOrCreate(
            ['email' => 'patient.carlos@medcore.com'],
            [
                'name' => 'Carlos Mendoza',
                'password' => 'Password123!',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $patientCarlosUser->syncRoles([$patientRole]);

        $patientCarlosProfile = PatientProfile::updateOrCreate(
            ['user_id' => $patientCarlosUser->id],
            [
                'date_of_birth' => '1985-11-22',
                'gender' => 'male',
                'blood_type' => $this->resolveBloodType('A+', 'APositive'),
                'phone' => '+1-555-0301',
                'allergies' => ['Sulfa drugs'],
                'chronic_conditions' => 'Controlled hypertension',
                'emergency_contact_name' => 'Sofia Mendoza',
                'emergency_contact_phone' => '+1-555-0302',
            ]
        );

        // 7. Completed Appointment for Ana with Dr. House (yesterday at 10:00) + MedicalRecord + 2 Prescriptions + AuditLog
        $yesterday10Am = now()->subDay()->setTime(10, 0, 0);
        $yesterday1030Am = (clone $yesterday10Am)->setTime(10, 30, 0);

        $anaAppointment = Appointment::updateOrCreate(
            [
                'patient_profile_id' => $patientAnaProfile->id,
                'doctor_profile_id' => $drHouseProfile->id,
                'scheduled_at' => $yesterday10Am,
            ],
            [
                'ends_at' => $yesterday1030Am,
                'status' => $this->resolveAppointmentStatus('completed', 'Completed'),
                'reason' => 'Severe episodic joint pain and intermittent fever',
                'notes' => 'Patient referred from primary clinic for comprehensive differential diagnosis.',
                'cancellation_reason' => null,
            ]
        );

        $anaRecord = MedicalRecord::updateOrCreate(
            [
                'appointment_id' => $anaAppointment->id,
            ],
            [
                'patient_profile_id' => $patientAnaProfile->id,
                'doctor_profile_id' => $drHouseProfile->id,
                'diagnosis' => 'Systemic Lupus Erythematosus (SLE) ruled out; diagnosis confirmed as Pulmonary Sarcoidosis (ICD-10 D86.0)',
                'clinical_notes' => 'Comprehensive immunological battery revealed elevated ACE levels and non-caseating granulomas on biopsy. Normal renal panel. Patient advised to avoid strenuous physical exertion.',
                'treatment_plan' => 'Initiate oral prednisone taper over 12 weeks with bone protection adjuvant therapy. Bi-weekly chest imaging follow-up.',
                'vital_signs' => [
                    'blood_pressure' => '118/76',
                    'heart_rate_bpm' => 74,
                    'temperature_c' => 37.1,
                    'spo2_percent' => 99,
                ],
                'recorded_at' => (clone $yesterday10Am)->setTime(10, 35, 0),
            ]
        );

        Prescription::updateOrCreate(
            [
                'medical_record_id' => $anaRecord->id,
                'medication_name' => 'Prednisone',
            ],
            [
                'dosage' => '20mg',
                'frequency' => 'Once daily each morning with breakfast',
                'duration_days' => 28,
                'instructions' => 'Taper dose as directed in patient portal after 4 weeks. Do not discontinue abruptly.',
            ]
        );

        Prescription::updateOrCreate(
            [
                'medical_record_id' => $anaRecord->id,
                'medication_name' => 'Calcium Carbonate with Vitamin D',
            ],
            [
                'dosage' => '500mg/400IU',
                'frequency' => 'Twice daily',
                'duration_days' => 90,
                'instructions' => 'Take with meals to support bone density during corticosteroid regimen.',
            ]
        );

        AuditLog::firstOrCreate(
            [
                'user_id' => $drHouseUser->id,
                'action' => 'medical_record.created',
                'auditable_type' => MedicalRecord::class,
                'auditable_id' => $anaRecord->id,
            ],
            [
                'ip_address' => '192.168.1.100',
                'user_agent' => 'MedCore-ClinicalClient/1.0 (Hospital-Workstation-4)',
                'metadata' => [
                    'patient_id' => $patientAnaProfile->id,
                    'appointment_id' => $anaAppointment->id,
                    'phi_accessed' => true,
                    'encryption' => 'AES-256-CBC',
                ],
                'created_at' => (clone $yesterday10Am)->setTime(10, 36, 0),
            ]
        );

        // 8. Confirmed Upcoming Appointment for Carlos with Dr. House (next Monday at 11:00)
        $carlosScheduledAt = now()->next('Monday')->setTime(11, 0, 0);
        $carlosEndsAt = (clone $carlosScheduledAt)->setTime(11, 30, 0);

        Appointment::updateOrCreate(
            [
                'patient_profile_id' => $patientCarlosProfile->id,
                'doctor_profile_id' => $drHouseProfile->id,
                'scheduled_at' => $carlosScheduledAt,
            ],
            [
                'ends_at' => $carlosEndsAt,
                'status' => $this->resolveAppointmentStatus('confirmed', 'Confirmed'),
                'reason' => 'Routine cardiology consultation and medication efficacy check',
                'notes' => 'Patient has maintained stable blood pressure logs over preceding month.',
                'cancellation_reason' => null,
            ]
        );
    }

    /**
     * Resolve a DayOfWeek enum instance from integer (1..7) or name.
     */
    private function resolveDayOfWeek(int $dayNumber, string $name): DayOfWeek
    {
        if (defined(DayOfWeek::class.'::'.$name)) {
            /** @var DayOfWeek */
            return constant(DayOfWeek::class.'::'.$name);
        }

        if (method_exists(DayOfWeek::class, 'tryFrom')) {
            $fromInt = DayOfWeek::tryFrom($dayNumber);
            if ($fromInt !== null) {
                return $fromInt;
            }

            $fromString = DayOfWeek::tryFrom(strtolower($name));
            if ($fromString !== null) {
                return $fromString;
            }
        }

        return DayOfWeek::cases()[$dayNumber - 1] ?? DayOfWeek::cases()[0];
    }

    /**
     * Resolve a BloodType enum instance from standard symbol (e.g. 'O+') or case name (e.g. 'OPositive').
     */
    private function resolveBloodType(string $symbol, string $caseName): BloodType
    {
        if (method_exists(BloodType::class, 'tryFrom')) {
            $fromSymbol = BloodType::tryFrom($symbol);
            if ($fromSymbol !== null) {
                return $fromSymbol;
            }
        }

        if (defined(BloodType::class.'::'.$caseName)) {
            /** @var BloodType */
            return constant(BloodType::class.'::'.$caseName);
        }

        return BloodType::cases()[0];
    }

    /**
     * Resolve an AppointmentStatus enum instance from value (e.g. 'completed') or case name (e.g. 'Completed').
     */
    private function resolveAppointmentStatus(string $value, string $caseName): AppointmentStatus
    {
        if (method_exists(AppointmentStatus::class, 'tryFrom')) {
            $fromValue = AppointmentStatus::tryFrom($value);
            if ($fromValue !== null) {
                return $fromValue;
            }
        }

        if (defined(AppointmentStatus::class.'::'.$caseName)) {
            /** @var AppointmentStatus */
            return constant(AppointmentStatus::class.'::'.$caseName);
        }

        return AppointmentStatus::cases()[0];
    }
}
