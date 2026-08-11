<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be configured before running AdminUserSeeder.'
            );
        }

        $configuredUser = User::where('email', $email)->first();

        if ($configuredUser && ! $configuredUser->is_admin) {
            throw new RuntimeException(
                'The configured admin email already belongs to a non-admin user. Resolve the user record manually before running AdminUserSeeder.'
            );
        }

        $admin = $configuredUser ?: User::where('is_admin', true)->first();

        if ($admin) {
            $admin->update([
                'name' => env('ADMIN_NAME', 'Lumina Admin'),
                'email' => $email,
                'password' => $password,
                'is_admin' => true,
            ]);

            return;
        }

        User::create([
            'name' => env('ADMIN_NAME', 'Lumina Admin'),
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ]);
    }
}
