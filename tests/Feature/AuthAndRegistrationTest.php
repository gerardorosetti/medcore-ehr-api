<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('doctor', 'web');
    Role::findOrCreate('patient', 'web');
});

it('registers a new patient with UUID, encrypts PHI at rest, issues bearer token, and logs audit trail', function (): void {
    $payload = [
        'name' => 'Eleanor Vance',
        'email' => 'eleanor.vance@example.com',
        'password' => 'PatientSecret123!',
        'password_confirmation' => 'PatientSecret123!',
        'date_of_birth' => '1988-11-23',
        'gender' => 'female',
        'blood_type' => 'O+',
        'phone' => '+1-555-4321',
        'allergies' => ['Penicillin', 'Sulfa'],
        'chronic_conditions' => 'Asthma and seasonal allergic rhinitis',
        'emergency_contact_name' => 'Theodora Vance',
        'emergency_contact_phone' => '+1-555-8765',
    ];

    $response = $this->postJson('/api/v1/auth/register', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'roles',
            ],
        ])
        ->assertJson([
            'message' => 'Patient registered successfully.',
            'token_type' => 'Bearer',
        ]);

    $user = User::query()->where('email', 'eleanor.vance@example.com')->firstOrFail();
    expect(Str::isUuid($user->id))->toBeTrue()
        ->and($user->hasRole('patient'))->toBeTrue();

    $profile = $user->patientProfile;
    expect($profile)->not->toBeNull();

    // Verify raw database ciphertext at rest (AES-256 Eloquent encrypted cast)
    $rawProfile = DB::table('patient_profiles')->where('id', $profile->id)->first();
    expect($rawProfile)->not->toBeNull()
        ->and($rawProfile->allergies)->not->toContain('Penicillin')
        ->and($rawProfile->chronic_conditions)->not->toContain('Asthma');

    // Verify transparent Eloquent decryption
    expect($profile->fresh()->allergies)->toBe(['Penicillin', 'Sulfa'])
        ->and($profile->fresh()->chronic_conditions)->toBe('Asthma and seasonal allergic rhinitis');

    // Verify HIPAA immutable audit log creation
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'auth.patient_registered',
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
    ]);
});

it('authenticates active users, returns scoped Sanctum token abilities, and logs login action', function (): void {
    $doctorUser = User::factory()->create([
        'email' => 'dr.watson@medcore.com',
        'password' => Hash::make('DoctorPass123!'),
        'is_active' => true,
    ]);
    $doctorUser->assignRole('doctor');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'dr.watson@medcore.com',
        'password' => 'DoctorPass123!',
        'device_name' => 'clinical-workstation',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'token',
            'token_type',
            'abilities',
            'user',
        ])
        ->assertJson([
            'message' => 'Login successful.',
            'token_type' => 'Bearer',
            'abilities' => RoleEnum::Doctor->defaultAbilities(),
        ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $doctorUser->id,
        'action' => 'auth.login',
        'auditable_type' => User::class,
        'auditable_id' => $doctorUser->id,
    ]);
});

it('rejects authentication with invalid credentials or unverified passwords', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('ValidSecret123!'),
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'IncorrectPassword!',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown.clinician@medcore.com',
        'password' => 'ValidSecret123!',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('prevents deactivated accounts from logging into the EHR platform', function (): void {
    $inactiveUser = User::factory()->create([
        'password' => Hash::make('ValidSecret123!'),
        'is_active' => false,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $inactiveUser->email,
        'password' => 'ValidSecret123!',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('retrieves authenticated user profile details on the introspection endpoint', function (): void {
    $user = User::factory()->create();
    $user->assignRole('patient');
    PatientProfile::factory()->create(['user_id' => $user->id]);

    $token = $user->createToken('session-token', RoleEnum::Patient->defaultAbilities())->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);

    app('auth')->forgetGuards();

    $this->withoutHeader('Authorization')
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('revokes personal access tokens during logout to terminate authenticated sessions', function (): void {
    $user = User::factory()->create();
    $user->assignRole('patient');
    $token = $user->createToken('session-token', ['*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJson([
            'message' => 'Successfully logged out.',
        ]);

    expect($user->tokens()->count())->toBe(0);

    app('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
