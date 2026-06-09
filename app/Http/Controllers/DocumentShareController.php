<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentShare;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentShareController extends Controller
{
    public function index(Request $request)
    {
        $shares = DocumentShare::query()
            ->with('document:id,original_name,owner_id', 'sender:id,name,email', 'receiver:id,name,email')
            ->where(function ($query) use ($request) {
                $query->where('sender_id', $request->user()->id)
                    ->orWhere('receiver_id', $request->user()->id);
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
        abort_unless($document->owner_id === $request->user()->id, 403);

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

        $this->audit($request, 'share_file', 'Document shared.', [
            'document_id' => $document->id,
            'receiver_id' => $share->receiver_id,
        ]);

        return response()->json($share->load('document', 'receiver:id,name,email'), 201);
    }

    public function show(Request $request, DocumentShare $documentShare)
    {
        abort_unless($this->canView($request, $documentShare), 403);

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
        abort_unless($documentShare->sender_id === $request->user()->id, 403);

        $validated = $request->validate([
            'permission' => ['required', Rule::in(['view', 'download'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $documentShare->update($validated);

        return response()->json($documentShare);
    }

    public function destroy(Request $request, DocumentShare $documentShare)
    {
        abort_unless($documentShare->sender_id === $request->user()->id, 403);

        $documentShare->delete();

        $this->audit($request, 'unshare_file', 'Document share revoked.', [
            'document_id' => $documentShare->document_id,
            'receiver_id' => $documentShare->receiver_id,
        ]);

        return response()->noContent();
    }

    private function canView(Request $request, DocumentShare $documentShare): bool
    {
        return $documentShare->sender_id === $request->user()->id
            || $documentShare->receiver_id === $request->user()->id;
    }

    private function audit(Request $request, string $activity, string $description, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'activity' => $activity,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
