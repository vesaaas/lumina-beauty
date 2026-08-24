<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LoginTwoFactorChallenge;
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
        $this->configureGoogleOAuth();

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

    public function test_google_redirect_fails_gracefully_when_credentials_are_missing(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => 'https://lumina-beauty.ddev.site/auth/google/callback',
        ]);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['email' => 'Google login is not configured yet.']);
    }

    public function test_configured_google_redirect_contains_client_id(): void
    {
        $this->configureGoogleOAuth();

        $response = $this->get(route('auth.google.redirect'));
        $location = $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertIsString($location);
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('client_id=test-google-client-id', $location);
        $this->assertStringNotContainsString('client_id=&', $location);
    }

    public function test_account_modal_exposes_google_login_button(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('auth.google.redirect'), false);
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
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'customer.oauth_google_login',
        ]);
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
        $this->assertSame(0, LoginTwoFactorChallenge::count());
    }

    public function test_google_callback_rejects_external_intended_redirect(): void
    {
        $this->mockGoogleUser('safe-google@example.com');

        $this->withSession(['url.intended' => 'https://evil.example/phishing'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_google_callback_preserves_safe_customer_intended_redirect(): void
    {
        $this->mockGoogleUser('safe-local-google@example.com');

        $this->withSession(['url.intended' => route('products.index')])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('products.index'));

        $this->assertAuthenticated();
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'oauth.google_admin_blocked',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
        ]);
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'oauth.google_failed',
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'oauth.google_failed',
        ]);
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

    private function configureGoogleOAuth(): void
    {
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => 'https://lumina-beauty.ddev.site/auth/google/callback',
        ]);
    }
}
