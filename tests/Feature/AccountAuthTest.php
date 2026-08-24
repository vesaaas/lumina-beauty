<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_stores_phone_number(): void
    {
        $this->post(route('register.submit'), [
            'first_name' => 'Vesa',
            'last_name' => 'Beauty',
            'email' => 'vesa@example.com',
            'phone' => '+383 44 111 222',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertDatabaseHas('users', [
            'name' => 'Vesa Beauty',
            'email' => 'vesa@example.com',
            'phone' => '+383 44 111 222',
            'is_admin' => false,
        ]);

        $user = User::where('email', 'vesa@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        $this->assertDatabaseHas('email_verification_otps', [
            'user_id' => $user->id,
            'attempts' => 0,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registration.completed',
        ]);
    }

    public function test_weak_registration_password_is_rejected(): void
    {
        $response = $this->from(route('home'))->post(route('register.submit'), [
            'first_name' => 'Vesa',
            'last_name' => 'Beauty',
            'email' => 'weak@example.com',
            'phone' => '+383 44 111 222',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');

        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');

        $this->assertDatabaseMissing('users', [
            'email' => 'weak@example.com',
        ]);
    }

    public function test_login_validation_does_not_flash_password_input(): void
    {
        $response = $this->from(route('home'))->post(route('login.submit'), [
            'email' => 'missing@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('_old_input.password');
    }

    public function test_valid_strong_registration_password_is_accepted(): void
    {
        $this->post(route('register.submit'), [
            'first_name' => 'Strong',
            'last_name' => 'Customer',
            'email' => 'strong@example.com',
            'phone' => '+383 44 111 222',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertDatabaseHas('users', [
            'email' => 'strong@example.com',
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset.requested',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_customer_password_reset_completion_is_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-complete@example.com',
            'password' => 'Password123!',
            'is_admin' => false,
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset-complete@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset.completed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
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
