<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DocumentShareController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', DocumentShare::class);

        $shares = DocumentShare::query()
            ->with('document:id,original_name,owner_id', 'sender:id,name,email', 'receiver:id,name,email')
            ->when(! $request->user()->hasRole('admin'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('sender_id', $request->user()->id)
                        ->orWhere('receiver_id', $request->user()->id);
                });
            })
            ->latest()
            ->paginate(15);

        return response()->json($shares);
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_id' => ['required', 'integer', 'exists:documents,id'],
            'receiver_id' => ['required', 'integer', 'exists:users,id', Rule::notIn([$request->user()->id])],
            'permission' => ['required', Rule::in(['view', 'download'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $document = Document::query()->findOrFail($validated['document_id']);
        Gate::authorize('update', $document);

        $share = DocumentShare::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'receiver_id' => $validated['receiver_id'],
            ],
            [
                'sender_id' => $request->user()->id,
                'permission' => $validated['permission'],
                'message' => $validated['message'] ?? null,
                'status' => 'sent',
                'read_at' => null,
                'downloaded_at' => null,
            ],
        );

        app(AuditLogger::class)->record($request, 'share_file', 'Document shared.', [
            'document_id' => $document->id,
            'receiver_id' => $share->receiver_id,
        ]);

        return response()->json($share->load('document', 'receiver:id,name,email'), 201);
    }

    public function show(Request $request, DocumentShare $documentShare)
    {
        Gate::authorize('view', $documentShare);

        if ($documentShare->receiver_id === $request->user()->id && $documentShare->read_at === null) {
            $documentShare->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }

        return response()->json($documentShare->load('document', 'sender:id,name,email', 'receiver:id,name,email'));
    }

    public function edit(DocumentShare $documentShare)
    {
        abort(404);
    }

    public function update(Request $request, DocumentShare $documentShare)
    {
        Gate::authorize('update', $documentShare);

        $validated = $request->validate([
            'permission' => ['required', Rule::in(['view', 'download'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $documentShare->update($validated);

        return response()->json($documentShare);
    }

    public function destroy(Request $request, DocumentShare $documentShare)
    {
        Gate::authorize('delete', $documentShare);

        $documentShare->delete();

        app(AuditLogger::class)->record($request, 'unshare_file', 'Document share revoked.', [
            'document_id' => $documentShare->document_id,
            'receiver_id' => $documentShare->receiver_id,
        ]);

        return response()->noContent();
    }
}
