<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class AuditLogController
 *
 * Exposes compliance audit trail query endpoints restricted strictly to clinical administrators.
 */
class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of HIPAA audit logs.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return AnonymousResourceCollection Paginated collection of AuditLog resources.
     */
    #[\NoDiscard]
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless(
            $request->user()?->hasRole('admin'),
            403,
            'Only clinical compliance administrators may inspect the HIPAA audit trail.'
        );

        $validated = $request->validate([
            'action' => ['sometimes', 'string', 'max:80'],
        ]);

        $query = AuditLog::query()
            ->with('user')
            ->when(
                isset($validated['action']) && $validated['action'] !== '',
                fn ($q) => $q->where('action', $validated['action'])
            )
            ->orderByDesc('created_at');

        $logs = $query->paginate(25);

        return AuditLogResource::collection($logs);
    }
}
