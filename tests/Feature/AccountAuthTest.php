<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_stores_phone_number(): void
    {
        $this->post(route('register.submit'), [
            'first_name' => 'Elira',
            'last_name' => 'Beauty',
            'email' => 'elira@example.com',
            'phone' => '+383 44 111 222',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'name' => 'Elira Beauty',
            'email' => 'elira@example.com',
            'phone' => '+383 44 111 222',
            'is_admin' => false,
        ]);
    }

    public function test_customer_can_request_password_reset_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
        ]);

        $this->post(route('password.email'), [
            'email' => 'customer@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_admin_password_reset_is_not_sent_from_customer_flow(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => 'admin@example.com',
        ])->assertSessionHasErrors('email');

        Notification::assertNotSentTo($admin, ResetPassword::class);
    }
}
