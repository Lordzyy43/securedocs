<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Document::class);

        $documents = Document::query()
            ->with('owner:id,name,email')
            ->when(! $request->user()->hasRole('admin'), function ($query) use ($request) {
                $query->where('owner_id', $request->user()->id);
            })
            ->latest()
            ->paginate(15);

        return response()->json($documents);
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document' => [
                'required',
                File::types(['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png'])->max(10 * 1024),
            ],
        ]);

        $file = $validated['document'];
        $contents = $file->get();
        $path = 'documents/'.$request->user()->id.'/'.Str::uuid().'.bin';

        Storage::disk('local')->put($path, Crypt::encryptString($contents));

        $document = Document::query()->create([
            'owner_id' => $request->user()->id,
            'file_name' => Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_hash' => hash('sha256', $contents),
            'encrypted' => true,
        ]);

        app(AuditLogger::class)->record($request, 'upload', 'Document uploaded.', ['document_id' => $document->id]);

        return response()->json($document, 201);
    }

    public function show(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        return response()->json($document->load('owner:id,name,email', 'shares.receiver:id,name,email'));
    }

    public function edit(Document $document)
    {
        abort(404);
    }

    public function update(Request $request, Document $document)
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
        ]);

        $document->update($validated);
        app(AuditLogger::class)->record($request, 'update', 'Document metadata updated.', ['document_id' => $document->id]);

        return response()->json($document);
    }

    public function destroy(Request $request, Document $document)
    {
        Gate::authorize('delete', $document);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        app(AuditLogger::class)->record($request, 'delete', 'Document deleted.', ['document_id' => $document->id]);

        return response()->noContent();
    }

    public function download(Request $request, Document $document)
    {
        Gate::authorize('download', $document);

        $encrypted = Storage::disk('local')->get($document->file_path);
        $contents = Crypt::decryptString($encrypted);

        app(AuditLogger::class)->record($request, 'download', 'Document downloaded.', ['document_id' => $document->id]);

        return response($contents, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'attachment; filename="'.$document->original_name.'"',
        ]);
    }
}
