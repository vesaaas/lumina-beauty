<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
{
    $password = env('ADMIN_PASSWORD');

    if (blank($password)) {
        throw new \RuntimeException(
            'ADMIN_PASSWORD must be configured in the .env file before running AdminUserSeeder.'
        );
    }

   $admin = User::where('is_admin', true)->first();

if ($admin) {
    $admin->update([
        'name' => env('ADMIN_NAME', 'Lumina Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => $password,
    ]);

    return;
}

User::create([
    'name' => env('ADMIN_NAME', 'Lumina Admin'),
    'email' => env('ADMIN_EMAIL'),
    'password' => $password,
    'is_admin' => true,
]);
}
}
