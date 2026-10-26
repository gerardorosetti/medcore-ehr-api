<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrescriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Prescription
 *
 * Represents a medication order prescribed in a medical consultation record.
 *
 * @property string $id
 * @property string $medical_record_id
 * @property string $medication_name
 * @property string $dosage
 * @property string $frequency
 * @property int $duration_days
 * @property string|null $instructions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MedicalRecord $medicalRecord
 */
class Prescription extends Model
{
    /** @use HasFactory<PrescriptionFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'medical_record_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration_days',
        'instructions',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'medication_name' => 'encrypted',
            'dosage' => 'encrypted',
            'frequency' => 'encrypted',
            'duration_days' => 'integer',
            'instructions' => 'encrypted',
        ];
    }

    /**
     * Get the medical record associated with this prescription.
     *
     * @return BelongsTo<MedicalRecord, $this>
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
