<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTestUserWithRole(string $roleName, string $status = 'active'): User
{
    $role = Role::query()->firstOrCreate(['name' => $roleName]);
    return User::factory()->create([
        'role_id' => $role->id,
        'status' => $status
    ]);
}

test('any authenticated user can list active users for sharing', function () {
    $user1 = createTestUserWithRole('user');
    $user2 = createTestUserWithRole('user');
    $user3 = createTestUserWithRole('user', 'inactive');
    $admin = createTestUserWithRole('admin');

    // As user1, fetch /users
    $response = $this->actingAs($user1)
        ->getJson('/users')
        ->assertSuccessful();

    // Should contain active normal users only, but NOT self, inactive users, or admins.
    $data = $response->json();
    expect($data)->toHaveCount(1);

    $ids = collect($data)->pluck('id')->toArray();
    expect($ids)->toContain($user2->id)
        ->not->toContain($admin->id)
        ->not->toContain($user1->id)
        ->not->toContain($user3->id);
    
    // Check fields returned (should only be id, name, email)
    expect($data[0])->toHaveKeys(['id', 'name', 'email'])
        ->not->toHaveKeys(['role_id', 'status', 'role']);
});

test('non-admin user cannot access admin users endpoints', function () {
    $user = createTestUserWithRole('user');
    $otherUser = createTestUserWithRole('user');

    $this->actingAs($user)->getJson('/admin/users')->assertForbidden();
    $this->actingAs($user)->postJson('/admin/users', [])->assertForbidden();
    $this->actingAs($user)->putJson("/admin/users/{$otherUser->id}", [])->assertForbidden();
    $this->actingAs($user)->putJson("/admin/users/{$otherUser->id}/toggle-status")->assertForbidden();
});

test('admin can list all users with pagination', function () {
    $admin = createTestUserWithRole('admin');
    $user1 = createTestUserWithRole('user');
    $user2 = createTestUserWithRole('user', 'inactive');

    $response = $this->actingAs($admin)
        ->getJson('/admin/users')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'role_id', 'status', 'role']
            ]
        ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(3); // admin, user1, user2
});

test('admin can create user', function () {
    $admin = createTestUserWithRole('admin');
    $role = Role::query()->firstOrCreate(['name' => 'user']);

    $response = $this->actingAs($admin)
        ->postJson('/admin/users', [
            'name' => 'New Guy',
            'email' => 'newguy@example.com',
            'password' => 'secretpassword123',
            'role_id' => $role->id,
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'New Guy')
        ->assertJsonPath('email', 'newguy@example.com')
        ->assertJsonPath('role.name', 'user');

    $this->assertDatabaseHas('users', [
        'email' => 'newguy@example.com',
        'status' => UserStatus::ACTIVE->value,
    ]);

    // Check audit log
    expect(AuditLog::query()
        ->where('activity', 'user_management')
        ->where('description', "User 'newguy@example.com' created.")
        ->exists())->toBeTrue();
});

test('admin can update user', function () {
    $admin = createTestUserWithRole('admin');
    $user = createTestUserWithRole('user');
    $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

    $response = $this->actingAs($admin)
        ->putJson("/admin/users/{$user->id}", [
            'name' => 'Updated Guy',
            'email' => 'updatedguy@example.com',
            'role_id' => $adminRole->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('name', 'Updated Guy')
        ->assertJsonPath('email', 'updatedguy@example.com')
        ->assertJsonPath('role.name', 'admin');

    // Check audit log
    expect(AuditLog::query()
        ->where('activity', 'user_management')
        ->where('description', "User 'updatedguy@example.com' updated.")
        ->exists())->toBeTrue();
});

test('admin cannot update their own role', function () {
    $admin = createTestUserWithRole('admin');
    $userRole = Role::query()->firstOrCreate(['name' => 'user']);

    $response = $this->actingAs($admin)
        ->putJson("/admin/users/{$admin->id}", [
            'name' => 'Still Admin',
            'email' => $admin->email,
            'role_id' => $userRole->id, // trying to change self role to user
        ])
        ->assertForbidden(); // self-lockout prevention
});

test('admin can toggle user status', function () {
    $admin = createTestUserWithRole('admin');
    $user = createTestUserWithRole('user', 'active');

    // Deactivate
    $this->actingAs($admin)
        ->putJson("/admin/users/{$user->id}/toggle-status")
        ->assertSuccessful()
        ->assertJsonPath('status', UserStatus::INACTIVE->value);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => UserStatus::INACTIVE->value,
    ]);

    // Activate
    $this->actingAs($admin)
        ->putJson("/admin/users/{$user->id}/toggle-status")
        ->assertSuccessful()
        ->assertJsonPath('status', UserStatus::ACTIVE->value);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => UserStatus::ACTIVE->value,
    ]);

    // Check audit logs
    expect(AuditLog::query()
        ->where('activity', 'user_management')
        ->where('description', "User '{$user->email}' status toggled to inactive.")
        ->exists())->toBeTrue();
});

test('admin cannot toggle their own status', function () {
    $admin = createTestUserWithRole('admin');

    $this->actingAs($admin)
        ->putJson("/admin/users/{$admin->id}/toggle-status")
        ->assertForbidden(); // self-lockout prevention
});
