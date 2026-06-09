<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(25);

        return response()->json($logs);
    }

    public function create()
    {
        abort(404);
    }

    public function store()
    {
        abort(404);
    }

    public function show(AuditLog $auditLog)
    {
        Gate::authorize('view', $auditLog);

        return response()->json($auditLog->load('user:id,name,email'));
    }

    public function edit(AuditLog $auditLog)
    {
        abort(404);
    }

    public function update(AuditLog $auditLog)
    {
        abort(404);
    }

    public function destroy(AuditLog $auditLog)
    {
        abort(404);
    }
}
