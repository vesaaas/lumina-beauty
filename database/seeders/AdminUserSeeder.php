<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@luminabeauty.test')],
            [
                'name' => env('ADMIN_NAME', 'Lumina Admin'),
                'password' => env('ADMIN_PASSWORD', 'Admin123!'),
                'is_admin' => true,
            ],
        );
    }
}
