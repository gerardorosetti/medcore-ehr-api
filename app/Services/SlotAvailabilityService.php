<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use Carbon\CarbonImmutable;

/**
 * Service calculating real-time clinical appointment slot availability for doctors.
 */
readonly class SlotAvailabilityService
{
    /**
     * Calculate all available consultation slots for a given doctor on a specified date.
     *
     * @param  string  $date  ISO date string (Y-m-d)
     * @return array<int, array{start_time: string, end_time: string, scheduled_at: string, duration_minutes: int}>
     */
    #[\NoDiscard]
    public function getAvailableSlots(DoctorProfile $doctor, string $date): array
    {
        $targetDate = CarbonImmutable::parse($date)->startOfDay();

        if (! $doctor->is_accepting_patients) {
            return [];
        }

        $schedules = $doctor->schedules()
            ->where('day_of_week', $targetDate->dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $bookedAppointments = Appointment::query()
            ->where('doctor_profile_id', $doctor->id)
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->whereDate('scheduled_at', $targetDate->toDateString())
            ->get(['scheduled_at', 'ends_at']);

        $duration = max(10, $doctor->consultation_duration_minutes ?: 30);
        $availableSlots = [];

        foreach ($schedules as $schedule) {
            $slotStart = CarbonImmutable::parse($targetDate->toDateString().' '.$schedule->start_time);
            $scheduleEnd = CarbonImmutable::parse($targetDate->toDateString().' '.$schedule->end_time);

            while ($slotStart->addMinutes($duration)->lte($scheduleEnd)) {
                $slotEnd = $slotStart->addMinutes($duration);

                $isOverlapping = $bookedAppointments->contains(function (Appointment $appt) use ($slotStart, $slotEnd): bool {
                    return $appt->scheduled_at < $slotEnd && $appt->ends_at > $slotStart;
                });

                if (! $isOverlapping) {
                    $availableSlots[] = [
                        'start_time' => $slotStart->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                        'scheduled_at' => $slotStart->toIso8601String(),
                        'duration_minutes' => $duration,
                    ];
                }

                $slotStart = $slotEnd;
            }
        }

        return $availableSlots;
    }
}
