<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class Appointment
 *
 * Represents an appointment booking between a patient and a doctor, tracking scheduling,
 * status transitions, cancellation metadata, and the associated medical consultation record.
 *
 * @property string $id
 * @property string $patient_profile_id
 * @property string $doctor_profile_id
 * @property Carbon $scheduled_at
 * @property Carbon $ends_at
 * @property AppointmentStatus|string $status
 * @property string $reason
 * @property string|null $notes
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PatientProfile $patientProfile
 * @property-read DoctorProfile $doctorProfile
 * @property-read MedicalRecord|null $medicalRecord
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, HasUuids;

    /**
     * Columns permitted for query sorting and filtering.
     *
     * @var array<int, string>
     */
    public const array ALLOWED_SORT_COLUMNS = [
        'scheduled_at',
        'status',
        'created_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_profile_id',
        'doctor_profile_id',
        'scheduled_at',
        'ends_at',
        'status',
        'reason',
        'notes',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'reason' => 'encrypted',
            'notes' => 'encrypted',
            'cancellation_reason' => 'encrypted',
        ];
    }

    /**
     * Get the patient profile for this appointment.
     *
     * @return BelongsTo<PatientProfile, $this>
     */
    public function patientProfile(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class);
    }

    /**
     * Get the doctor profile for this appointment.
     *
     * @return BelongsTo<DoctorProfile, $this>
     */
    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    /**
     * Get the clinical medical record created during this appointment.
     *
     * @return HasOne<MedicalRecord, $this>
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }
}
