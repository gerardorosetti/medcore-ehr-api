<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BloodType;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource representation for a patient demographic and medical profile.
 *
 * @mixin PatientProfile
 */
class PatientProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $bloodType = $this->blood_type instanceof BloodType
            ? $this->blood_type->value
            : $this->blood_type;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'blood_type' => $bloodType,
            'phone' => $this->phone,
            'allergies' => $this->allergies,
            'chronic_conditions' => $this->chronic_conditions,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
