<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $userRole = Role::query()->firstOrCreate([
            'name' => UserRole::USER->value
        ]);

        $adminRole = Role::query()->firstOrCreate([
            'name' => UserRole::ADMIN->value
        ]);

        // Admin
        User::query()->updateOrCreate(
            [
                'email' => 'admin@securedocs.test',
            ],
            [
                'name' => 'SecureDocs Admin',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]
        );

        // User Default
        User::query()->updateOrCreate(
            [
                'email' => 'user@securedocs.test',
            ],
            [
                'name' => 'SecureDocs User',
                'password' => Hash::make('password'),
                'role_id' => $userRole->id,
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]
        );

        // Dummy Users
        for ($i = 1; $i <= 10; $i++) {
            User::query()->updateOrCreate(
                [
                    'email' => "user{$i}@securedocs.test",
                ],
                [
                    'name' => "Dummy User {$i}",
                    'password' => Hash::make('password'),
                    'role_id' => $userRole->id,
                    'status' => UserStatus::ACTIVE->value,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
