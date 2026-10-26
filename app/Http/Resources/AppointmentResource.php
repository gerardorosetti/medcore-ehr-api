<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AppointmentResource
 *
 * Transforms clinical appointment Eloquent models into sanitized JSON response representations.
 *
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_profile_id' => $this->patient_profile_id,
            'doctor_profile_id' => $this->doctor_profile_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status instanceof AppointmentStatus ? $this->status->value : $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'patient' => new PatientProfileResource($this->whenLoaded('patientProfile')),
            'doctor' => new DoctorProfileResource($this->whenLoaded('doctorProfile')),
            'has_medical_record' => $this->whenLoaded('medicalRecord', fn () => $this->medicalRecord !== null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
