<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a role can be assigned to a user', function () {
    $role = Role::query()->create(['name' => 'admin']);
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->assertModelExists($role);
    expect($user->refresh())
        ->role->name->toBe('admin')
        ->hasRole('admin')->toBeTrue()
        ->isActive()->toBeTrue();
});
