<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;

/**
 * Class MedicalRecordPolicy
 *
 * Enforces role-based authorization rules and identity boundaries for viewing
 * and authoring Electronic Health Records (EHR) under HIPAA compliance guidelines.
 */
class MedicalRecordPolicy
{
    /**
     * Determine whether the user can view any medical records.
     *
     * @param  User  $user  The authenticated user requesting the index listing.
     * @return bool True if authorized; false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'patient']);
    }

    /**
     * Determine whether the user can view the specific medical record.
     *
     * Administrators have unrestricted compliance oversight access.
     * Doctors may only view records authored under their doctor profile.
     * Patients may only view records belonging to their own patient profile.
     *
     * @param  User  $user  The authenticated user requesting to view the record.
     * @param  MedicalRecord  $medicalRecord  The clinical medical record instance.
     * @return bool True if authorized; false otherwise.
     */
    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $medicalRecord->doctorProfile?->user_id === $user->id;
        }

        if ($user->hasRole('patient')) {
            return $medicalRecord->patientProfile?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can author a clinical consultation record for the given appointment.
     *
     * Only the treating doctor assigned to the appointment (or an admin who is also
     * the assigned doctor) can author a consultation record.
     *
     * @param  User  $user  The authenticated user attempting to create the record.
     * @param  Appointment  $appointment  The target appointment.
     * @return bool True if authorized; false otherwise.
     */
    public function createForAppointment(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('doctor') && $appointment->doctorProfile?->user_id === $user->id;
    }
}
