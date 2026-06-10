<?php

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createProfileTestUser(string $roleName): User
{
    $role = Role::query()->firstOrCreate(['name' => $roleName]);
    return User::factory()->create(['role_id' => $role->id]);
}

function createTestDocument(User $owner, string $contents = 'hello plain text'): Document
{
    Storage::disk('local')->put("documents/{$owner->id}/doc.bin", Crypt::encryptString($contents));

    return Document::query()->create([
        'owner_id' => $owner->id,
        'file_name' => 'doc.bin',
        'original_name' => 'plain.txt',
        'file_path' => "documents/{$owner->id}/doc.bin",
        'file_size' => strlen($contents),
        'mime_type' => 'text/plain',
        'file_hash' => hash('sha256', $contents),
        'encrypted' => true,
    ]);
}

test('user can update their own profile info', function () {
    $user = createProfileTestUser('user');

    $response = $this->actingAs($user)
        ->putJson('/profile', [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ])
        ->assertSuccessful()
        ->assertJsonPath('user.name', 'New Name')
        ->assertJsonPath('user.email', 'newemail@example.com');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'newemail@example.com',
    ]);

    expect(AuditLog::query()
        ->where('user_id', $user->id)
        ->where('activity', 'profile_update')
        ->exists())->toBeTrue();
});

test('user can change their password', function () {
    $user = createProfileTestUser('user');
    $user->update(['password' => Hash::make('oldpassword123')]);

    // Incorrect old password
    $this->actingAs($user)
        ->putJson('/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertStatus(422);

    // Correct change password
    $this->actingAs($user)
        ->putJson('/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertSuccessful();

    $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));

    expect(AuditLog::query()
        ->where('user_id', $user->id)
        ->where('activity', 'password_change')
        ->exists())->toBeTrue();
});

test('owner can preview document content', function () {
    Storage::fake('local');
    $owner = createProfileTestUser('user');
    $document = createTestDocument($owner, 'owner secret plaintext');

    $response = $this->actingAs($owner)
        ->get("/documents/{$document->id}/preview")
        ->assertSuccessful()
        ->assertHeader('Content-Disposition', 'inline; filename="plain.txt"; filename*=UTF-8\'\'plain.txt')
        ->assertContent('owner secret plaintext');

    expect(AuditLog::query()
        ->where('user_id', $owner->id)
        ->where('activity', 'preview')
        ->exists())->toBeTrue();
});

test('recipient with view permission can preview document content', function () {
    Storage::fake('local');
    $owner = createProfileTestUser('user');
    $receiver = createProfileTestUser('user');
    $document = createTestDocument($owner, 'shared view only content');

    DocumentShare::query()->create([
        'document_id' => $document->id,
        'sender_id' => $owner->id,
        'receiver_id' => $receiver->id,
        'permission' => 'view',
        'status' => 'sent',
    ]);

    $this->actingAs($receiver)
        ->get("/documents/{$document->id}/preview")
        ->assertSuccessful()
        ->assertContent('shared view only content');
});

test('recipient with download permission can preview document content', function () {
    Storage::fake('local');
    $owner = createProfileTestUser('user');
    $receiver = createProfileTestUser('user');
    $document = createTestDocument($owner, 'shared download content');

    DocumentShare::query()->create([
        'document_id' => $document->id,
        'sender_id' => $owner->id,
        'receiver_id' => $receiver->id,
        'permission' => 'download',
        'status' => 'sent',
    ]);

    $this->actingAs($receiver)
        ->get("/documents/{$document->id}/preview")
        ->assertSuccessful()
        ->assertContent('shared download content');
});

test('admin cannot preview document content', function () {
    Storage::fake('local');
    $owner = createProfileTestUser('user');
    $admin = createProfileTestUser('admin');
    $document = createTestDocument($owner);

    $this->actingAs($admin)
        ->get("/documents/{$document->id}/preview")
        ->assertForbidden(); // Admin cannot decrypt/preview content
});

test('unrelated user cannot preview document content', function () {
    Storage::fake('local');
    $owner = createProfileTestUser('user');
    $other = createProfileTestUser('user');
    $document = createTestDocument($owner);

    $this->actingAs($other)
        ->get("/documents/{$document->id}/preview")
        ->assertForbidden();
});
