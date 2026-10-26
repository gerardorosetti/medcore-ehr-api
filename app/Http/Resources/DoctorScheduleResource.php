<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\DayOfWeek;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource representation for a doctor's weekly availability schedule slot.
 *
 * @mixin DoctorSchedule
 */
class DoctorScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dayOfWeekValue = $this->day_of_week instanceof DayOfWeek
            ? $this->day_of_week->value
            : (int) $this->day_of_week;

        $dayLabel = $this->day_of_week instanceof DayOfWeek
            ? $this->day_of_week->label()
            : DayOfWeek::from((int) $this->day_of_week)->label();

        return [
            'id' => $this->id,
            'day_of_week' => $dayOfWeekValue,
            'day_label' => $dayLabel,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
