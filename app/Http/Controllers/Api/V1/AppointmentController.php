<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AppointmentQueryRequest;
use App\Http\Requests\Appointment\CancelAppointmentRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Class AppointmentController
 *
 * REST API controller managing clinical appointment queries, bookings, cancellations,
 * and associated role-based access control.
 */
class AppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a paginated listing of appointments scoped by user role.
     */
    public function index(AppointmentQueryRequest $request, AuditLogService $auditLogService): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        /** @var User $user */
        $user = $request->user();
        $query = Appointment::query();

        // Scope queries by role: non-admin doctors and patients only view their own appointments
        if ($user->hasRole('doctor') && ! $user->hasRole('admin')) {
            $query->where('doctor_profile_id', $user->doctorProfile?->id);
        } elseif ($user->hasRole('patient') && ! $user->hasRole('admin')) {
            $query->where('patient_profile_id', $user->patientProfile?->id);
        }

        $validated = $request->validated();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['from_date'])) {
            $query->whereDate('scheduled_at', '>=', $validated['from_date']);
        }

        if (! empty($validated['to_date'])) {
            $query->whereDate('scheduled_at', '<=', $validated['to_date']);
        }

        $sortBy = $validated['sort_by'] ?? 'scheduled_at';
        $direction = $validated['direction'] ?? 'asc';

        if (! in_array($sortBy, Appointment::ALLOWED_SORT_COLUMNS, true)) {
            $sortBy = 'scheduled_at';
        }

        if (! in_array(strtolower($direction), ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $query->orderBy($sortBy, $direction);

        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 15;

        $appointments = $query->with(['patientProfile.user', 'doctorProfile.user', 'medicalRecord'])
            ->paginate($perPage);

        $auditLogService->log(
            $user,
            'appointment.listed_phi',
            $user,
            [
                'appointment_count' => $appointments->count(),
                'appointment_ids' => $appointments->pluck('id')->all(),
                'page' => $appointments->currentPage(),
            ],
            $request
        );

        return AppointmentResource::collection($appointments);
    }

    /**
     * Book a new clinical appointment.
     *
     *
     * @throws ValidationException
     */
    public function store(StoreAppointmentRequest $request, AppointmentService $service): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        // Admins may schedule on behalf of a specific patient; patients schedule for themselves
        if ($user->hasRole('admin') && ! empty($validated['patient_profile_id'])) {
            $patientProfile = PatientProfile::findOrFail($validated['patient_profile_id']);
        } else {
            $patientProfile = $user->patientProfile;
            if ($patientProfile === null) {
                throw ValidationException::withMessages([
                    'patient_profile_id' => ['Authenticated user does not have a valid patient profile.'],
                ]);
            }
        }

        /** @var DoctorProfile $doctorProfile */
        $doctorProfile = DoctorProfile::findOrFail($validated['doctor_profile_id']);

        $appointment = $service->schedule(
            patient: $patientProfile,
            doctor: $doctorProfile,
            scheduledAt: CarbonImmutable::parse($validated['scheduled_at']),
            reason: $validated['reason'],
            notes: $validated['notes'] ?? null,
            actor: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified appointment and record a HIPAA PHI access audit event.
     */
    public function show(Request $request, Appointment $appointment, AuditLogService $auditLogService): AppointmentResource
    {
        $appointment->load(['patientProfile.user', 'doctorProfile.user', 'medicalRecord']);

        $this->authorize('view', $appointment);

        $auditLogService->log(
            $request->user(),
            'appointment.viewed_phi',
            $appointment,
            [
                'patient_profile_id' => $appointment->patient_profile_id,
                'doctor_profile_id' => $appointment->doctor_profile_id,
            ],
            $request
        );

        return new AppointmentResource($appointment);
    }

    /**
     * Cancel an existing appointment.
     */
    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        AppointmentService $service
    ): AppointmentResource {
        $appointment->load(['patientProfile.user', 'doctorProfile.user']);

        $this->authorize('cancel', $appointment);

        /** @var User $user */
        $user = $request->user();

        $updated = $service->cancel(
            appointment: $appointment,
            cancellationReason: $request->validated('cancellation_reason'),
            actor: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return new AppointmentResource($updated);
    }
}
