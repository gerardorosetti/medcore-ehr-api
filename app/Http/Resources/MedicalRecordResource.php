<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class MedicalRecordResource
 *
 * Transforms the MedicalRecord model into an API response payload,
 * formatting encrypted clinical notes, vital signs, and associated relationship resources.
 *
 * @mixin MedicalRecord
 */
class MedicalRecordResource extends JsonResource
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
            'appointment_id' => $this->appointment_id,
            'patient_profile_id' => $this->patient_profile_id,
            'doctor_profile_id' => $this->doctor_profile_id,
            'diagnosis' => $this->diagnosis,
            'clinical_notes' => $this->clinical_notes,
            'treatment_plan' => $this->treatment_plan,
            'vital_signs' => $this->vital_signs,
            'recorded_at' => $this->recorded_at?->toISOString(),
            'prescriptions' => PrescriptionResource::collection($this->whenLoaded('prescriptions')),
            'patient' => class_exists(PatientProfileResource::class)
                ? new PatientProfileResource($this->whenLoaded('patientProfile'))
                : $this->whenLoaded('patientProfile'),
            'doctor' => class_exists(DoctorProfileResource::class)
                ? new DoctorProfileResource($this->whenLoaded('doctorProfile'))
                : $this->whenLoaded('doctorProfile'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
