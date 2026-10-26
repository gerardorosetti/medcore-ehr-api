<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * System roles available in the MedCore EHR API.
 */
enum RoleEnum: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Patient = 'patient';

    /**
     * Get the human-readable display label for the role.
     */
    #[\NoDiscard]
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Doctor => 'Doctor',
            self::Patient => 'Patient',
        };
    }

    /**
     * Get the default Sanctum token abilities for this role.
     *
     * @return list<string>
     */
    #[\NoDiscard]
    public function defaultAbilities(): array
    {
        return match ($this) {
            self::Admin => ['*'],
            self::Doctor => [
                'appointments:read',
                'appointments:write',
                'medical-records:read',
                'medical-records:write',
                'prescriptions:write',
            ],
            self::Patient => [
                'appointments:read',
                'appointments:write',
                'medical-records:read',
            ],
        };
    }

    /**
     * Get an array of all role values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
