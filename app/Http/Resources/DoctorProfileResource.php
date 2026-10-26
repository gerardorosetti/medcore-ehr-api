<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DoctorProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource representation for a clinical practitioner doctor profile.
 *
 * @mixin DoctorProfile
 */
class DoctorProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'specialty' => $this->specialty,
            'license_number' => $this->license_number,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'consultation_duration_minutes' => $this->consultation_duration_minutes,
            'is_accepting_patients' => (bool) $this->is_accepting_patients,
            'schedules' => DoctorScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
