<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentScheduled;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\PatientProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Class AppointmentService
 *
 * Handles clinical appointment scheduling, conflict detection, pessimistic locking,
 * state transitions, and HIPAA-compliant audit logging.
 */
readonly class AppointmentService
{
    /**
     * Sanitize clinical reason and notes text using PHP 8.5 pipe syntax.
     */
    private function sanitizeClinicalText(string $input): string
    {
        return $input
            |> (fn (string $text): string => preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $text) ?? $text)
            |> strip_tags(...)
            |> trim(...)
            |> (fn (string $text): string => preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    /**
     * Schedule a new appointment with pessimistic locking and validation of doctor availability.
     *
     *
     * @throws ValidationException
     */
    #[\NoDiscard]
    public function schedule(
        PatientProfile $patient,
        DoctorProfile $doctor,
        CarbonInterface $scheduledAt,
        string $reason,
        ?string $notes = null,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Appointment {
        return DB::transaction(function () use (
            $patient,
            $doctor,
            $scheduledAt,
            $reason,
            $notes,
            $actor,
            $ipAddress,
            $userAgent
        ): Appointment {
            // Acquire a pessimistic row lock on the DoctorProfile to prevent race conditions
            /** @var DoctorProfile $lockedDoctor */
            $lockedDoctor = DoctorProfile::query()
                ->whereKey($doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedDoctor->is_accepting_patients) {
                throw ValidationException::withMessages([
                    'doctor_profile_id' => ['This doctor is not currently accepting new appointments.'],
                ]);
            }

            $start = CarbonImmutable::instance($scheduledAt)->seconds(0);
            $durationMinutes = $lockedDoctor->consultation_duration_minutes ?: 30;
            $end = $start->addMinutes($durationMinutes);

            // Verify active working schedule on the requested day of the week
            $hasActiveSchedule = DoctorSchedule::query()
                ->where('doctor_profile_id', $doctor->id)
                ->where('is_active', true)
                ->where('day_of_week', $start->dayOfWeek)
                ->where('start_time', '<=', $start->format('H:i:s'))
                ->where('end_time', '>=', $end->format('H:i:s'))
                ->exists();

            if (! $hasActiveSchedule) {
                throw ValidationException::withMessages([
                    'scheduled_at' => ["The requested time falls outside the doctor's active working hours."],
                ]);
            }

            // Check for overlapping active appointments for this doctor
            $hasConflict = Appointment::query()
                ->where('doctor_profile_id', $doctor->id)
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
                ->where('scheduled_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'scheduled_at' => ['The requested time slot is already booked for this doctor.'],
                ]);
            }

            $sanitizedReason = $this->sanitizeClinicalText($reason);
            $sanitizedNotes = $notes !== null ? $this->sanitizeClinicalText($notes) : null;

            $appointment = Appointment::create([
                'patient_profile_id' => $patient->id,
                'doctor_profile_id' => $doctor->id,
                'scheduled_at' => $start,
                'ends_at' => $end,
                'status' => AppointmentStatus::Confirmed,
                'reason' => $sanitizedReason,
                'notes' => $sanitizedNotes,
            ]);

            AuditLog::create([
                'action' => 'appointment.scheduled',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'user_id' => $actor?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'doctor_profile_id' => $doctor->id,
                    'patient_profile_id' => $patient->id,
                    'scheduled_at' => $start->toIso8601String(),
                ],
                'created_at' => CarbonImmutable::now(),
            ]);

            if (class_exists(AppointmentScheduled::class)) {
                event(new AppointmentScheduled($appointment));
            }

            return $appointment->load(['patientProfile.user', 'doctorProfile.user']);
        });
    }

    /**
     * Cancel an active appointment with state transition enforcement and audit logging.
     *
     *
     * @throws ValidationException
     */
    #[\NoDiscard]
    public function cancel(
        Appointment $appointment,
        string $cancellationReason,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Appointment {
        $status = $appointment->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::from((string) $appointment->status);

        if (! $status->canTransitionTo(AppointmentStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => ['This appointment cannot be cancelled from its current status.'],
            ]);
        }

        return DB::transaction(function () use (
            $appointment,
            $cancellationReason,
            $actor,
            $ipAddress,
            $userAgent
        ): Appointment {
            $sanitizedReason = $this->sanitizeClinicalText($cancellationReason);

            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'cancellation_reason' => $sanitizedReason,
            ]);

            AuditLog::create([
                'action' => 'appointment.cancelled',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'user_id' => $actor->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'cancellation_reason' => $sanitizedReason,
                ],
                'created_at' => CarbonImmutable::now(),
            ]);

            return $appointment->load(['patientProfile.user', 'doctorProfile.user']);
        });
    }
}
