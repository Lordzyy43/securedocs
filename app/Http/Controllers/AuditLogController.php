<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

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

    public function store(Request $request)
    {
        abort(404);
    }

    public function show(Request $request, AuditLog $auditLog)
    {
        $this->authorizeAdmin($request);

        return response()->json($auditLog->load('user:id,name,email'));
    }

    public function edit(AuditLog $auditLog)
    {
        abort(404);
    }

    public function update(Request $request, AuditLog $auditLog)
    {
        abort(404);
    }

    public function destroy(AuditLog $auditLog)
    {
        abort(404);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
