<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorProfile>
 */
class DoctorProfileFactory extends Factory
{
    /**
     * The model that the factory applies to.
     *
     * @var class-string<DoctorProfile>
     */
    protected $model = DoctorProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'specialty' => fake()->randomElement([
                'Cardiology',
                'Neurology',
                'Internal Medicine',
                'Pediatrics',
                'Psychiatry',
                'Endocrinology',
                'Diagnostic Medicine',
                'Dermatology',
                'Orthopedics',
            ]),
            'license_number' => 'MED-'.fake()->unique()->numerify('######'),
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->paragraph(),
            'consultation_duration_minutes' => 30,
            'is_accepting_patients' => true,
        ];
    }

    /**
     * Indicate that the doctor is currently not accepting new patients.
     */
    public function notAcceptingPatients(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_accepting_patients' => false,
        ]);
    }
}
