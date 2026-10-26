<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterPatientRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Controller handling user authentication, patient registration, session introspection, and logout.
 */
class AuthController extends Controller
{
    /**
     * Authenticate an existing user and issue a scoped Sanctum personal access token.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect or the account is inactive.'],
            ]);
        }

        $roleName = (string) $user->getRoleNames()->first();
        $roleEnum = RoleEnum::tryFrom($roleName) ?? RoleEnum::Patient;

        $deviceName = $validated['device_name'] ?? 'api-client';
        $token = $user->createToken($deviceName, $roleEnum->defaultAbilities())->plainTextToken;

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $user->load(['doctorProfile.schedules', 'patientProfile']);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'Bearer',
            'abilities' => $roleEnum->defaultAbilities(),
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Register a new patient user and profile within an atomic database transaction.
     */
    public function register(RegisterPatientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        [$user, $token] = DB::transaction(function () use ($validated, $request): array {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => true,
            ]);

            $patientRole = Role::findOrCreate('patient', 'web');
            $user->assignRole($patientRole);

            $user->patientProfile()->create([
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'allergies' => $validated['allergies'] ?? null,
                'chronic_conditions' => $validated['chronic_conditions'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            ]);

            $plainTextToken = $user->createToken(
                'api-client',
                RoleEnum::Patient->defaultAbilities()
            )->plainTextToken;

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.patient_registered',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return [$user, $plainTextToken];
        });

        $user->load(['doctorProfile.schedules', 'patientProfile']);

        return response()->json([
            'message' => 'Patient registered successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Get the authenticated user's profile and active roles.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['doctorProfile.schedules', 'patientProfile']);

        return (new UserResource($user))->response();
    }

    /**
     * Revoke the current access token used for authentication.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }
}
