<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * The model that the factory applies to.
     *
     * @var class-string<Appointment>
     */
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduledAt = now()->addDay()->setTime(10, 0, 0);
        $endsAt = (clone $scheduledAt)->setTime(10, 30, 0);

        return [
            'patient_profile_id' => PatientProfile::factory(),
            'doctor_profile_id' => DoctorProfile::factory(),
            'scheduled_at' => $scheduledAt,
            'ends_at' => $endsAt,
            'status' => AppointmentStatus::Confirmed,
            'reason' => fake()->randomElement([
                'Routine annual physical examination',
                'Follow-up consultation for hypertension',
                'Chronic back pain evaluation',
                'Mild persistent cough and fatigue',
                'Review of laboratory diagnostic results',
            ]),
            'notes' => fake()->sentence(),
            'cancellation_reason' => null,
        ];
    }
}
