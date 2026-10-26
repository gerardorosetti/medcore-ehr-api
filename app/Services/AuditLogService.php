<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class AuditLogService
 *
 * Provides centralized, immutable audit logging to comply with HIPAA requirements (§164.312(b)),
 * recording every Protected Health Information (PHI) access event, authoring session, and mutation.
 */
readonly class AuditLogService
{
    /**
     * Record an immutable audit log entry for a clinical or administrative event.
     *
     * @param  User|null  $actor  The authenticated user performing the action, or null for system events.
     * @param  string  $action  Standardized action identifier (e.g. 'medical_record.created', 'medical_record.viewed_phi').
     * @param  Model  $auditable  The target Eloquent entity being accessed or mutated.
     * @param  array<string, mixed>  $metadata  Contextual compliance metadata associated with the event.
     * @param  Request|null  $request  The incoming HTTP request instance for capturing client network metadata.
     * @return AuditLog The newly persisted immutable audit log entry.
     */
    public function log(
        ?User $actor,
        string $action,
        Model $auditable,
        array $metadata = [],
        ?Request $request = null
    ): AuditLog {
        $auditableId = $auditable->getKey() |> strval(...);

        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditableId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
