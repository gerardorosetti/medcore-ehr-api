<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PrescriptionResource
 *
 * Transforms a clinical medication order (Prescription) into an API response payload.
 *
 * @mixin Prescription
 */
class PrescriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->medical_record_id,
            'medication_name' => $this->medication_name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration_days' => $this->duration_days,
            'instructions' => $this->instructions,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
