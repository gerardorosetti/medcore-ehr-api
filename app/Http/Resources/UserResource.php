<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource representation for an authenticated user account.
 *
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,
            'roles' => $this->getRoleNames()->values(),
            'doctor_profile' => new DoctorProfileResource($this->whenLoaded('doctorProfile')),
            'patient_profile' => new PatientProfileResource($this->whenLoaded('patientProfile')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
