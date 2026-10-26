<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Class MedicalConsultationService
 *
 * Coordinates clinical consultation completion, including transactional persistence of
 * encrypted Protected Health Information (PHI), medication orders (prescriptions),
 * appointment status progression, and HIPAA compliance audit logging.
 */
readonly class MedicalConsultationService
{
    /**
     * Create a new service instance.
     *
     * @param  AuditLogService  $auditLogService  The HIPAA compliance audit log service.
     */
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    /**
     * Complete a medical consultation for an appointment, authoring the medical record and prescriptions.
     *
     * @param  Appointment  $appointment  The appointment being completed.
     * @param  User  $doctorUser  The authenticated treating doctor completing the consultation.
     * @param  array<string, mixed>  $validatedData  Validated consultation payload including diagnosis and prescriptions.
     * @param  Request|null  $request  The incoming HTTP request for client tracking.
     * @return MedicalRecord The newly created medical record with relationships eager loaded.
     *
     * @throws ValidationException If the appointment status transition is invalid or a record already exists.
     */
    #[\NoDiscard]
    public function completeConsultation(
        Appointment $appointment,
        User $doctorUser,
        array $validatedData,
        ?Request $request = null
    ): MedicalRecord {
        $status = $appointment->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::from((string) $appointment->status);

        if (! $status->canTransitionTo(AppointmentStatus::Completed)) {
            throw ValidationException::withMessages([
                'appointment' => ['Consultation can only be recorded for a confirmed appointment.'],
            ]);
        }

        if ($appointment->medicalRecord()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => ['A medical record has already been issued for this appointment.'],
            ]);
        }

        return DB::transaction(function () use ($appointment, $doctorUser, $validatedData, $request): MedicalRecord {
            $medicalRecord = MedicalRecord::create([
                'appointment_id' => $appointment->id,
                'patient_profile_id' => $appointment->patient_profile_id,
                'doctor_profile_id' => $appointment->doctor_profile_id,
                'diagnosis' => $validatedData['diagnosis'],
                'clinical_notes' => $validatedData['clinical_notes'],
                'treatment_plan' => $validatedData['treatment_plan'] ?? null,
                'vital_signs' => $validatedData['vital_signs'] ?? null,
                'recorded_at' => now(),
            ]);

            $prescriptions = $validatedData['prescriptions'] ?? [];
            foreach ($prescriptions as $prescriptionData) {
                $medicalRecord->prescriptions()->create([
                    'medication_name' => $prescriptionData['medication_name'],
                    'dosage' => $prescriptionData['dosage'],
                    'frequency' => $prescriptionData['frequency'],
                    'duration_days' => (int) $prescriptionData['duration_days'],
                    'instructions' => $prescriptionData['instructions'] ?? null,
                ]);
            }

            $appointment->update([
                'status' => AppointmentStatus::Completed,
            ]);

            $prescriptionsCount = $prescriptions |> count(...);

            $this->auditLogService->log(
                $doctorUser,
                'medical_record.created',
                $medicalRecord,
                [
                    'appointment_id' => $appointment->id,
                    'patient_profile_id' => $appointment->patient_profile_id,
                    'prescriptions_count' => $prescriptionsCount,
                    'phi_encrypted' => true,
                ],
                $request
            );

            return $medicalRecord->load([
                'prescriptions',
                'patientProfile.user',
                'doctorProfile.user',
                'appointment',
            ]);
        });
    }
}
