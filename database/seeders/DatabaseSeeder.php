<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $userRole = Role::query()->firstOrCreate(['name' => 'user']);
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

        User::query()->updateOrCreate([
            'email' => 'admin@securedocs.test',
        ], [
            'name' => 'SecureDocs Admin',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::query()->updateOrCreate([
            'email' => 'user@securedocs.test',
        ], [
            'name' => 'SecureDocs User',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
