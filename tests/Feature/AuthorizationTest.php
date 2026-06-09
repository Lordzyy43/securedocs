<?php

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeUserWithRole(string $roleName): User
{
    $role = Role::query()->firstOrCreate(['name' => $roleName]);

    return User::factory()->create(['role_id' => $role->id]);
}

function makeDocumentFor(User $owner, string $contents = 'secret document'): Document
{
    Storage::disk('local')->put('documents/'.$owner->id.'/test.bin', Crypt::encryptString($contents));

    return Document::query()->create([
        'owner_id' => $owner->id,
        'file_name' => 'encrypted.bin',
        'original_name' => 'contract.pdf',
        'file_path' => 'documents/'.$owner->id.'/test.bin',
        'file_size' => strlen($contents),
        'mime_type' => 'application/pdf',
        'file_hash' => hash('sha256', $contents),
        'encrypted' => true,
    ]);
}

test('unshared user cannot view update delete or download another user document', function () {
    Storage::fake('local');

    $owner = makeUserWithRole('user');
    $otherUser = makeUserWithRole('user');
    $document = makeDocumentFor($owner);

    $this->actingAs($otherUser)->getJson("/documents/{$document->id}")->assertForbidden();
    $this->actingAs($otherUser)->patchJson("/documents/{$document->id}", [
        'original_name' => 'renamed.pdf',
    ])->assertForbidden();
    $this->actingAs($otherUser)->deleteJson("/documents/{$document->id}")->assertForbidden();
    $this->actingAs($otherUser)->getJson("/documents/{$document->id}/download")->assertForbidden();
});

test('admin can list and view all documents', function () {
    Storage::fake('local');

    $admin = makeUserWithRole('admin');
    $firstOwner = makeUserWithRole('user');
    $secondOwner = makeUserWithRole('user');
    $firstDocument = makeDocumentFor($firstOwner);
    makeDocumentFor($secondOwner);

    $this->actingAs($admin)
        ->getJson('/documents')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->actingAs($admin)
        ->getJson("/documents/{$firstDocument->id}")
        ->assertSuccessful()
        ->assertJsonPath('id', $firstDocument->id);
});

test('shared receiver can view but view permission cannot download document', function () {
    Storage::fake('local');

    $owner = makeUserWithRole('user');
    $receiver = makeUserWithRole('user');
    $document = makeDocumentFor($owner);

    DocumentShare::query()->create([
        'document_id' => $document->id,
        'sender_id' => $owner->id,
        'receiver_id' => $receiver->id,
        'permission' => 'view',
        'status' => 'sent',
    ]);

    $this->actingAs($receiver)
        ->getJson("/documents/{$document->id}")
        ->assertSuccessful()
        ->assertJsonPath('id', $document->id);

    $this->actingAs($receiver)
        ->getJson("/documents/{$document->id}/download")
        ->assertForbidden();
});

test('receiver with download permission can download shared document', function () {
    Storage::fake('local');

    $owner = makeUserWithRole('user');
    $receiver = makeUserWithRole('user');
    $document = makeDocumentFor($owner, 'downloadable contents');

    DocumentShare::query()->create([
        'document_id' => $document->id,
        'sender_id' => $owner->id,
        'receiver_id' => $receiver->id,
        'permission' => 'download',
        'status' => 'sent',
    ]);

    $this->actingAs($receiver)
        ->get("/documents/{$document->id}/download")
        ->assertSuccessful()
        ->assertContent('downloadable contents');

    expect(AuditLog::query()
        ->whereBelongsTo($receiver)
        ->where('activity', 'download')
        ->exists())->toBeTrue();
});

test('only admin can read audit logs', function () {
    $admin = makeUserWithRole('admin');
    $user = makeUserWithRole('user');
    $auditLog = AuditLog::query()->create([
        'user_id' => $user->id,
        'activity' => 'upload',
        'description' => 'Document uploaded.',
    ]);

    $this->actingAs($user)->getJson('/audit-logs')->assertForbidden();
    $this->actingAs($user)->getJson("/audit-logs/{$auditLog->id}")->assertForbidden();

    $this->actingAs($admin)->getJson('/audit-logs')->assertSuccessful();
    $this->actingAs($admin)
        ->getJson("/audit-logs/{$auditLog->id}")
        ->assertSuccessful()
        ->assertJsonPath('id', $auditLog->id);
});
