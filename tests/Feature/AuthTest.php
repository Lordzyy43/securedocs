<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    Auth::logout();
});

test('active user can login and an audit log is recorded', function () {
    $role = Role::query()->create(['name' => 'user']);
    $user = User::factory()->create([
        'email' => 'active@example.com',
        'role_id' => $role->id,
    ]);

    $this->postJson('/login', [
        'email' => 'active@example.com',
        'password' => 'password',
    ])
        ->assertSuccessful()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.role.name', 'user');

    $this->assertAuthenticatedAs($user);

    expect(AuditLog::query()
        ->whereBelongsTo($user)
        ->where('activity', 'login')
        ->where('status', 'success')
        ->exists())->toBeTrue();
});

test('login fails with invalid credentials and records failed login', function () {
    User::factory()->create(['email' => 'active@example.com']);

    $this->postJson('/login', [
        'email' => 'active@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422);

    $this->assertGuest();

    expect(AuditLog::query()
        ->whereNull('user_id')
        ->where('activity', 'failed_login')
        ->where('status', 'failure')
        ->exists())->toBeTrue();
});

test('inactive user cannot login and is audited', function () {
    $user = User::factory()->create([
        'email' => 'inactive@example.com',
        'status' => 'inactive',
    ]);

    $this->postJson('/login', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account is inactive.');

    $this->assertGuest();

    expect(AuditLog::query()
        ->whereBelongsTo($user)
        ->where('activity', 'failed_login')
        ->where('status', 'failure')
        ->exists())->toBeTrue();
});

test('inactive authenticated user is logged out when accessing protected route', function () {
    $user = User::factory()->create([
        'status' => 'inactive',
    ]);

    $this->actingAs($user)
        ->getJson('/me')
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account is inactive.');

    $this->assertGuest();

    expect(AuditLog::query()
        ->whereBelongsTo($user)
        ->where('activity', 'inactive_session_blocked')
        ->where('status', 'failure')
        ->exists())->toBeTrue();
});

test('authenticated user can fetch current user and logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/me')
        ->assertSuccessful()
        ->assertJsonPath('email', $user->email);

    $this->postJson('/logout')
        ->assertSuccessful()
        ->assertJsonPath('message', 'Logged out.');

    $this->assertGuest();

    expect(AuditLog::query()
        ->whereBelongsTo($user)
        ->where('activity', 'logout')
        ->where('status', 'success')
        ->exists())->toBeTrue();
});
