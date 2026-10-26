<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AuditLogResource
 *
 * Transforms the immutable HIPAA AuditLog model into an API response payload
 * for compliance auditors and system administrators.
 *
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'actor_email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'action' => $this->action,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
