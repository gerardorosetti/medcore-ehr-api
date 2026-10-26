<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalRecord\StoreMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Services\AuditLogService;
use App\Services\MedicalConsultationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Class MedicalRecordController
 *
 * Handles clinical consultation record management, role-scoped queries,
 * Protected Health Information (PHI) view auditing, and consultation completion.
 */
class MedicalRecordController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a paginated listing of medical records scoped by the authenticated user's role.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return AnonymousResourceCollection Paginated collection of MedicalRecord resources.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MedicalRecord::class);

        $user = $request->user();

        $query = MedicalRecord::query()
            ->with(['prescriptions', 'patientProfile.user', 'doctorProfile.user'])
            ->orderByDesc('recorded_at');

        if ($user->hasRole('admin')) {
            // Unrestricted view for compliance and system administrators
        } elseif ($user->hasRole('doctor')) {
            $query->where('doctor_profile_id', $user->doctorProfile?->id);
        } elseif ($user->hasRole('patient')) {
            $query->where('patient_profile_id', $user->patientProfile?->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        $perPage = $request->integer('per_page', 15);
        $records = $query->paginate($perPage > 0 ? $perPage : 15);

        return MedicalRecordResource::collection($records);
    }

    /**
     * Display the specified medical record and record a HIPAA PHI access audit event.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  MedicalRecord  $medicalRecord  The requested clinical medical record.
     * @param  AuditLogService  $auditLogService  The HIPAA compliance audit log service.
     * @return MedicalRecordResource The transformed medical record resource.
     */
    public function show(
        Request $request,
        MedicalRecord $medicalRecord,
        AuditLogService $auditLogService
    ): MedicalRecordResource {
        $medicalRecord->load(['prescriptions', 'patientProfile.user', 'doctorProfile.user', 'appointment']);

        $this->authorize('view', $medicalRecord);

        $auditLogService->log(
            $request->user(),
            'medical_record.viewed_phi',
            $medicalRecord,
            ['patient_profile_id' => $medicalRecord->patient_profile_id],
            $request
        );

        return new MedicalRecordResource($medicalRecord);
    }

    /**
     * Complete a medical consultation for an appointment and author its clinical record.
     *
     * @param  StoreMedicalRecordRequest  $request  The validated consultation request.
     * @param  Appointment  $appointment  The appointment being completed.
     * @param  MedicalConsultationService  $service  The medical consultation domain service.
     * @return JsonResponse Created response containing the newly issued medical record.
     */
    public function storeForAppointment(
        StoreMedicalRecordRequest $request,
        Appointment $appointment,
        MedicalConsultationService $service
    ): JsonResponse {
        $appointment->load(['doctorProfile', 'patientProfile']);

        Gate::authorize('createForAppointment', [MedicalRecord::class, $appointment]);

        $record = $service->completeConsultation(
            $appointment,
            $request->user(),
            $request->validated(),
            $request
        );

        return (new MedicalRecordResource($record))
            ->response()
            ->setStatusCode(201);
    }
}
