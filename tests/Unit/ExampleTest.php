<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\BloodType;
use App\Enums\DayOfWeek;
use App\Enums\RoleEnum;

it('validates AppointmentStatus lifecycle transition matrix', function (): void {
    // Valid transitions from Pending
    expect(AppointmentStatus::Pending->canTransitionTo(AppointmentStatus::Confirmed))->toBeTrue()
        ->and(AppointmentStatus::Pending->canTransitionTo(AppointmentStatus::Cancelled))->toBeTrue()
        // Invalid transitions from Pending
        ->and(AppointmentStatus::Pending->canTransitionTo(AppointmentStatus::Completed))->toBeFalse()
        ->and(AppointmentStatus::Pending->canTransitionTo(AppointmentStatus::Pending))->toBeFalse();

    // Valid transitions from Confirmed
    expect(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Completed))->toBeTrue()
        ->and(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Cancelled))->toBeTrue()
        // Invalid transitions from Confirmed
        ->and(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Pending))->toBeFalse()
        ->and(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Confirmed))->toBeFalse();

    // Terminal statuses (Completed & Cancelled) cannot transition anywhere
    foreach (AppointmentStatus::cases() as $target) {
        expect(AppointmentStatus::Completed->canTransitionTo($target))->toBeFalse()
            ->and(AppointmentStatus::Cancelled->canTransitionTo($target))->toBeFalse();
    }
});

it('verifies AppointmentStatus active state detection for scheduling and double-booking', function (): void {
    expect(AppointmentStatus::Pending->isActive())->toBeTrue()
        ->and(AppointmentStatus::Confirmed->isActive())->toBeTrue()
        ->and(AppointmentStatus::Completed->isActive())->toBeFalse()
        ->and(AppointmentStatus::Cancelled->isActive())->toBeFalse();
});

it('returns all AppointmentStatus string values', function (): void {
    expect(AppointmentStatus::values())->toBe([
        'pending',
        'confirmed',
        'completed',
        'cancelled',
    ]);
});

it('validates RoleEnum labels and string values', function (): void {
    expect(RoleEnum::Admin->label())->toBe('Admin')
        ->and(RoleEnum::Doctor->label())->toBe('Doctor')
        ->and(RoleEnum::Patient->label())->toBe('Patient')
        ->and(RoleEnum::values())->toBe(['admin', 'doctor', 'patient']);
});

it('configures distinct Sanctum token abilities for each RoleEnum', function (): void {
    expect(RoleEnum::Admin->defaultAbilities())->toBe(['*'])
        ->and(RoleEnum::Doctor->defaultAbilities())->toBe([
            'appointments:read',
            'appointments:write',
            'medical-records:read',
            'medical-records:write',
            'prescriptions:write',
        ])
        ->and(RoleEnum::Patient->defaultAbilities())->toBe([
            'appointments:read',
            'appointments:write',
            'medical-records:read',
        ]);
});

it('maps DayOfWeek integer values to English weekday labels', function (): void {
    expect(DayOfWeek::Sunday->label())->toBe('Sunday')
        ->and(DayOfWeek::Monday->label())->toBe('Monday')
        ->and(DayOfWeek::Tuesday->label())->toBe('Tuesday')
        ->and(DayOfWeek::Wednesday->label())->toBe('Wednesday')
        ->and(DayOfWeek::Thursday->label())->toBe('Thursday')
        ->and(DayOfWeek::Friday->label())->toBe('Friday')
        ->and(DayOfWeek::Saturday->label())->toBe('Saturday')
        ->and(DayOfWeek::values())->toBe([0, 1, 2, 3, 4, 5, 6]);
});

it('enumerates all clinical BloodType ABO and Rh values', function (): void {
    expect(BloodType::values())->toBe([
        'A+',
        'A-',
        'B+',
        'B-',
        'AB+',
        'AB-',
        'O+',
        'O-',
    ]);
});
