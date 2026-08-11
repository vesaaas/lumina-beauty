<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_developer_provisioned_admin(): void
    {
        $this->withAdminEnvironment(function (): void {
            $this->seed(AdminUserSeeder::class);

            $admin = User::where('email', 'seeded-admin@example.com')->firstOrFail();

            $this->assertTrue($admin->is_admin);
            $this->assertTrue(Hash::check('Password123!', $admin->password));
        });
    }

    public function test_admin_user_seeder_updates_existing_admin_without_removing_admin_privilege(): void
    {
        $admin = User::factory()->create([
            'email' => 'old-admin@example.com',
            'password' => 'OldPassword123!',
            'is_admin' => true,
        ]);

        $this->withAdminEnvironment(function () use ($admin): void {
            $this->seed(AdminUserSeeder::class);

            $admin->refresh();

            $this->assertSame('seeded-admin@example.com', $admin->email);
            $this->assertTrue($admin->is_admin);
            $this->assertTrue(Hash::check('Password123!', $admin->password));
        });
    }

    public function test_admin_user_seeder_does_not_promote_non_admin_email_collision(): void
    {
        User::factory()->create([
            'email' => 'seeded-admin@example.com',
            'is_admin' => false,
        ]);

        $this->withAdminEnvironment(function (): void {
            $this->expectException(RuntimeException::class);

            $this->seed(AdminUserSeeder::class);
        });

        $this->assertDatabaseHas('users', [
            'email' => 'seeded-admin@example.com',
            'is_admin' => false,
        ]);
    }

    private function withAdminEnvironment(callable $callback): void
    {
        $originalEmail = env('ADMIN_EMAIL');
        $originalPassword = env('ADMIN_PASSWORD');
        $originalName = env('ADMIN_NAME');

        $this->setEnvironmentValue('ADMIN_EMAIL', 'seeded-admin@example.com');
        $this->setEnvironmentValue('ADMIN_PASSWORD', 'Password123!');
        $this->setEnvironmentValue('ADMIN_NAME', 'Seeded Admin');

        try {
            $callback();
        } finally {
            $this->setEnvironmentValue('ADMIN_EMAIL', $originalEmail);
            $this->setEnvironmentValue('ADMIN_PASSWORD', $originalPassword);
            $this->setEnvironmentValue('ADMIN_NAME', $originalName);
        }
    }

    private function setEnvironmentValue(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            return;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
