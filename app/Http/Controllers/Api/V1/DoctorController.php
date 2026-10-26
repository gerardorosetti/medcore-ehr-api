<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\DoctorQueryRequest;
use App\Http\Requests\Doctor\UpsertDoctorScheduleRequest;
use App\Http\Resources\DoctorProfileResource;
use App\Models\DoctorProfile;
use App\Services\SlotAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controller managing doctor profile catalog searches, detailed profiles, availability slots, and schedules.
 */
class DoctorController extends Controller
{
    /**
     * List doctor profiles with optional specialty filtering, status filtering, and sorting.
     */
    public function index(DoctorQueryRequest $request): AnonymousResourceCollection
    {
        $query = DoctorProfile::query()->with(['user', 'schedules']);

        if ($request->filled('specialty')) {
            $query->where('specialty', 'like', '%'.$request->input('specialty').'%');
        }

        if ($request->has('accepting_patients')) {
            $query->where('is_accepting_patients', $request->boolean('accepting_patients'));
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc'));

        if (in_array($sortBy, DoctorProfile::ALLOWED_SORT_COLUMNS, true)) {
            $query->orderBy($sortBy, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);
        $doctors = $query->paginate($perPage);

        return DoctorProfileResource::collection($doctors);
    }

    /**
     * Display a single doctor profile with loaded user and active schedules.
     */
    public function show(DoctorProfile $doctor): DoctorProfileResource
    {
        return new DoctorProfileResource($doctor->load(['user', 'schedules']));
    }

    /**
     * Calculate and return available appointment slots for a specific doctor on a given date.
     */
    public function availableSlots(Request $request, DoctorProfile $doctor, SlotAvailabilityService $slotService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $slots = $slotService->getAvailableSlots($doctor, $validated['date']);

        return response()->json([
            'data' => [
                'doctor_id' => $doctor->id,
                'date' => $validated['date'],
                'consultation_duration_minutes' => $doctor->consultation_duration_minutes,
                'available_slots' => $slots,
            ],
        ]);
    }

    /**
     * Replace or define the weekly consultation availability schedules for a doctor.
     */
    public function upsertSchedules(UpsertDoctorScheduleRequest $request, DoctorProfile $doctor): DoctorProfileResource
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole('admin') || ($user->hasRole('doctor') && $doctor->user_id === $user->id),
            403,
            'Unauthorized to modify this doctor schedule.'
        );

        $validated = $request->validated();

        DB::transaction(function () use ($doctor, $validated): void {
            $doctor->schedules()->delete();

            foreach ($validated['schedules'] as $schedule) {
                $startTime = Carbon::parse($schedule['start_time'])->format('H:i:00');
                $endTime = Carbon::parse($schedule['end_time'])->format('H:i:00');

                $doctor->schedules()->create([
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_active' => $schedule['is_active'] ?? true,
                ]);
            }
        });

        return new DoctorProfileResource($doctor->load(['user', 'schedules']));
    }
}
