<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class MedicalRecord
 *
 * Stores clinical consultation data, vital signs, and HIPAA PHI (diagnosis, clinical notes,
 * and treatment plans) encrypted at rest using AES-256 encryption.
 *
 * @property string $id
 * @property string $appointment_id
 * @property string $patient_profile_id
 * @property string $doctor_profile_id
 * @property string $diagnosis
 * @property string $clinical_notes
 * @property string $treatment_plan
 * @property array<string, mixed>|null $vital_signs
 * @property Carbon $recorded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Appointment $appointment
 * @property-read PatientProfile $patientProfile
 * @property-read DoctorProfile $doctorProfile
 * @property-read Collection<int, Prescription> $prescriptions
 */
class MedicalRecord extends Model
{
    /** @use HasFactory<MedicalRecordFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'appointment_id',
        'patient_profile_id',
        'doctor_profile_id',
        'diagnosis',
        'clinical_notes',
        'treatment_plan',
        'vital_signs',
        'recorded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diagnosis' => 'encrypted',
            'clinical_notes' => 'encrypted',
            'treatment_plan' => 'encrypted',
            'vital_signs' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the appointment during which this medical record was created.
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the patient profile to whom this medical record belongs.
     *
     * @return BelongsTo<PatientProfile, $this>
     */
    public function patientProfile(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class);
    }

    /**
     * Get the doctor profile who authored this medical record.
     *
     * @return BelongsTo<DoctorProfile, $this>
     */
    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    /**
     * Get all prescriptions prescribed in this medical record.
     *
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
