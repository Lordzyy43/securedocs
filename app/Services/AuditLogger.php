<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Request $request,
        string $activity,
        ?string $description = null,
        array $metadata = [],
        string $status = 'success',
        ?User $user = null,
    ): AuditLog {
        $resolvedUser = $user ?? $request->user();

        return AuditLog::query()->create([
            'user_id' => $resolvedUser?->id,
            'activity' => $activity,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }
}
