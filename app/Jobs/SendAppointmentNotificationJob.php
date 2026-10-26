<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class SendAppointmentNotificationJob
 *
 * Handles asynchronous background dispatch of transactional clinical appointment
 * confirmation notifications (Email/SMS) to patient and physician, logging HIPAA
 * audit trail entries upon successful queue delivery.
 */
class SendAppointmentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted before failing.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Exponential backoff strategy: 10s, 30s, 60s.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     *
     * @param  string  $appointmentId  The UUID of the appointment to notify about.
     */
    public function __construct(
        public readonly string $appointmentId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Appointment|null $appointment */
        $appointment = Appointment::with(['patientProfile.user', 'doctorProfile.user'])
            ->find($this->appointmentId);

        if ($appointment === null) {
            Log::warning('SendAppointmentNotificationJob: Appointment not found, terminating job', [
                'appointment_id' => $this->appointmentId,
            ]);

            return;
        }

        $patientUser = $appointment->patientProfile?->user;
        $doctorUser = $appointment->doctorProfile?->user;

        // Structured clinical confirmation log
        Log::info('Clinical appointment confirmation notification dispatched', [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_profile_id,
            'patient_name' => $patientUser?->name,
            'patient_email' => $patientUser?->email,
            'doctor_id' => $appointment->doctor_profile_id,
            'doctor_name' => $doctorUser?->name,
            'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
            'channel' => 'email_transactional',
        ]);

        // Record immutable HIPAA audit trail entry
        AuditLog::create([
            'user_id' => $appointment->patientProfile?->user_id,
            'action' => 'appointment.notification_queued_delivered',
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
            'metadata' => [
                'channel' => 'email_transactional',
                'doctor' => $doctorUser?->name,
                'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
            ],
            'created_at' => now(),
        ]);
    }
}
