<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_uses_socialite_driver(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_logs_in_existing_customer_by_verified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'google-customer@example.com',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->mockGoogleUser('google-customer@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_google_callback_creates_verified_customer_for_verified_google_email(): void
    {
        $this->mockGoogleUser('new-google@example.com', 'New Google');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $user = User::where('email', 'new-google@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_oauth_cannot_authenticate_admin_account(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-google@example.com',
            'is_admin' => true,
        ]);

        $this->mockGoogleUser('admin-google@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->mockGoogleUser('unverified-google@example.com', verified: false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'unverified-google@example.com',
        ]);
    }

    public function test_invalid_oauth_state_is_handled_safely(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new InvalidStateException());

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    private function mockGoogleUser(
        string $email,
        string $name = 'Google Customer',
        bool $verified = true
    ): void {
        $socialiteUser = new SocialiteUser();
        $socialiteUser->map([
            'id' => 'google-id-'.$email,
            'name' => $name,
            'email' => $email,
        ]);
        $socialiteUser->user = ['email_verified' => $verified];

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
