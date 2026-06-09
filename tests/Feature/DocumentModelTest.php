<?php

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('document sharing and audit log relationships work', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $document = Document::query()->create([
        'owner_id' => $owner->id,
        'file_name' => 'encrypted.bin',
        'original_name' => 'contract.pdf',
        'file_path' => 'documents/encrypted.bin',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
        'file_hash' => hash('sha256', 'contract'),
        'encrypted' => true,
    ]);

    $share = DocumentShare::query()->create([
        'document_id' => $document->id,
        'sender_id' => $owner->id,
        'receiver_id' => $receiver->id,
        'permission' => 'download',
        'status' => 'sent',
    ]);

    $auditLog = AuditLog::query()->create([
        'user_id' => $owner->id,
        'activity' => 'share_file',
        'description' => 'Document shared.',
        'status' => 'success',
        'metadata' => ['document_id' => $document->id],
    ]);

    expect($document->owner->is($owner))->toBeTrue()
        ->and($document->shares)->toHaveCount(1)
        ->and($share->document->is($document))->toBeTrue()
        ->and($share->sender->is($owner))->toBeTrue()
        ->and($share->receiver->is($receiver))->toBeTrue()
        ->and($auditLog->user->is($owner))->toBeTrue()
        ->and($auditLog->metadata)->toBe(['document_id' => $document->id]);
});
