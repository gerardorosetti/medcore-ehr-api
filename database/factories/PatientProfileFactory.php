<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BloodType;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientProfile>
 */
class PatientProfileFactory extends Factory
{
    /**
     * The model that the factory applies to.
     *
     * @var class-string<PatientProfile>
     */
    protected $model = PatientProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'blood_type' => BloodType::OPositive,
            'phone' => fake()->phoneNumber(),
            'allergies' => ['Penicillin'],
            'chronic_conditions' => 'Mild hypertension',
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
        ];
    }
}
