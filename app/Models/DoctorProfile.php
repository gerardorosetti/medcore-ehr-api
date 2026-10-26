<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DoctorProfileFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class DoctorProfile
 *
 * Represents clinical practitioner metadata, consultation settings, and relationships
 * to clinical schedules, appointments, and medical records.
 *
 * @property string $id
 * @property string $user_id
 * @property string $specialty
 * @property string $license_number
 * @property string|null $phone
 * @property string|null $bio
 * @property int $consultation_duration_minutes
 * @property bool $is_accepting_patients
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, DoctorSchedule> $schedules
 * @property-read Collection<int, Appointment> $appointments
 * @property-read Collection<int, MedicalRecord> $medicalRecords
 */
class DoctorProfile extends Model
{
    /** @use HasFactory<DoctorProfileFactory> */
    use HasFactory, HasUuids;

    /**
     * Columns permitted for query sorting and filtering.
     *
     * @var array<int, string>
     */
    public const array ALLOWED_SORT_COLUMNS = [
        'specialty',
        'consultation_duration_minutes',
        'created_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'specialty',
        'license_number',
        'phone',
        'bio',
        'consultation_duration_minutes',
        'is_accepting_patients',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consultation_duration_minutes' => 'integer',
            'is_accepting_patients' => 'boolean',
        ];
    }

    /**
     * Get the user account associated with this doctor profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the working schedules configured for this doctor.
     *
     * @return HasMany<DoctorSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    /**
     * Get the clinical appointments booked with this doctor.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the medical records authored or attended by this doctor.
     *
     * @return HasMany<MedicalRecord, $this>
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
