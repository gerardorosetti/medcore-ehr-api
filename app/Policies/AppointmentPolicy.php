<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Class AppointmentPolicy
 *
 * Implements role-based access control (RBAC) authorization rules for clinical appointments.
 */
class AppointmentPolicy
{
    /**
     * Determine whether the user can view any appointments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'patient']);
    }

    /**
     * Determine whether the user can view the appointment.
     *
     * Admins have full access. Doctors and patients may only view appointments
     * where their corresponding practitioner or patient profile is assigned.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $appointment->doctorProfile?->user_id === $user->id;
        }

        if ($user->hasRole('patient')) {
            return $appointment->patientProfile?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['patient', 'admin']);
    }

    /**
     * Determine whether the user can cancel the appointment.
     *
     * Applies the same ownership rules as viewing: admins, assigned doctor, or assigned patient.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $appointment->doctorProfile?->user_id === $user->id;
        }

        if ($user->hasRole('patient')) {
            return $appointment->patientProfile?->user_id === $user->id;
        }

        return false;
    }
}
