<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\PatientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * The model that the factory applies to.
     *
     * @var class-string<MedicalRecord>
     */
    protected $model = MedicalRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'patient_profile_id' => PatientProfile::factory(),
            'doctor_profile_id' => DoctorProfile::factory(),
            'diagnosis' => fake()->randomElement([
                'Essential hypertension (I10)',
                'Type 2 diabetes mellitus without complications (E11.9)',
                'Acute bronchitis, unspecified (J20.9)',
                'Generalized anxiety disorder (F41.1)',
                'Hyperlipidemia, unspecified (E78.5)',
            ]),
            'clinical_notes' => 'Patient presented with mild recurrent symptoms. Physical examination was unremarkable. Advised lifestyle modifications and regular monitoring.',
            'treatment_plan' => 'Initiate first-line pharmacotherapy. Follow up in four weeks with routine laboratory work.',
            'vital_signs' => [
                'blood_pressure' => '120/80',
                'heart_rate_bpm' => 72,
                'temperature_c' => 36.6,
                'spo2_percent' => 98,
            ],
            'recorded_at' => now(),
        ];
    }
}
