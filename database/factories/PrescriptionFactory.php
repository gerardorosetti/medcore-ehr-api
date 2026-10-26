<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * The model that the factory applies to.
     *
     * @var class-string<Prescription>
     */
    protected $model = Prescription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'medication_name' => 'Amoxicillin',
            'dosage' => '500mg',
            'frequency' => 'Every 8 hours',
            'duration_days' => 7,
            'instructions' => 'Take with food',
        ];
    }
}
