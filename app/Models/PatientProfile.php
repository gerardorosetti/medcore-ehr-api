<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BloodType;
use Database\Factories\PatientProfileFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class PatientProfile
 *
 * Stores patient demographics, medical history, and HIPAA-protected health information (PHI)
 * such as encrypted allergies and chronic conditions.
 *
 * @property string $id
 * @property string $user_id
 * @property Carbon $date_of_birth
 * @property string $gender
 * @property BloodType|string|null $blood_type
 * @property string|null $phone
 * @property array<int, string>|null $allergies
 * @property string|null $chronic_conditions
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Appointment> $appointments
 * @property-read Collection<int, MedicalRecord> $medicalRecords
 */
class PatientProfile extends Model
{
    /** @use HasFactory<PatientProfileFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'blood_type',
        'phone',
        'allergies',
        'chronic_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'blood_type' => BloodType::class,
            'allergies' => 'encrypted:array',
            'chronic_conditions' => 'encrypted',
        ];
    }

    /**
     * Get the user account associated with this patient profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all appointments scheduled for this patient.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get all medical records associated with this patient.
     *
     * @return HasMany<MedicalRecord, $this>
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
