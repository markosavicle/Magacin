<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first admin account from ADMIN_EMAIL / ADMIN_PASSWORD (and optional ADMIN_NAME),
 * read through config('app.admin') so it also works with a cached config.
 *
 * Safe to run on every deploy: an existing account is never changed, so a password set in the app
 * is not reset back to the one in .env. Without both variables it does nothing.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('AdminUserSeeder: ADMIN_EMAIL or ADMIN_PASSWORD is not set, no admin created.');
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name') ?: 'Admin',
                'password' => Hash::make($password),
                'is_admin' => true,
                'approved' => true,
            ]
        );

        $this->command?->info($user->wasRecentlyCreated
            ? "AdminUserSeeder: admin {$email} created."
            : "AdminUserSeeder: {$email} already exists, left unchanged.");
    }
}
