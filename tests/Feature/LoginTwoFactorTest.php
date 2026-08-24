<?php

namespace Tests\Feature;

use App\Mail\LoginTwoFactorCodeMail;
use App\Models\AuditLog;
use App\Models\LoginTwoFactorChallenge;
use App\Models\User;
use App\Services\LoginTwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_customer_password_does_not_immediately_authenticate_and_sends_2fa_mail(): void
    {
        [$user] = $this->startCustomerChallenge();

        $this->assertGuest();
        $this->assertDatabaseHas('login_two_factor_challenges', [
            'user_id' => $user->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
            'attempts' => 0,
        ]);
        Mail::assertQueued(
            LoginTwoFactorCodeMail::class,
            fn (LoginTwoFactorCodeMail $mail) => $mail->hasTo($user->email)
                && $mail->context === LoginTwoFactorChallenge::CONTEXT_CUSTOMER
                && $mail->afterCommit === true
                && Hash::check(
                    $mail->code,
                    LoginTwoFactorChallenge::where('user_id', $user->id)
                        ->where('context', LoginTwoFactorChallenge::CONTEXT_CUSTOMER)
                        ->firstOrFail()
                        ->code_hash
                )
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.login_2fa_challenge_initiated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_customer_2fa_challenge_renders_as_auth_dialog(): void
    {
        $this->startCustomerChallenge();

        $this->get(route('login.2fa.show'))
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('Security verification')
            ->assertSee('data-sensitive-code', false);
    }

    public function test_correct_customer_2fa_code_completes_login_and_clears_pending_state(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('home'))
            ->assertSessionMissing('login_2fa');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'customer.login_2fa_success',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'customer.login',
        ]);
    }

    public function test_customer_2fa_rejects_external_intended_redirect(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        $this->replacePendingIntended('https://evil.example/phishing');

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_2fa_preserves_safe_local_intended_redirect(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        $this->replacePendingIntended(route('products.index'));

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('products.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_2fa_rejects_admin_intended_redirect(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        $this->replacePendingIntended(route('admin.dashboard'));

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_customer_2fa_code_fails_and_increments_attempts(): void
    {
        [$user] = $this->startCustomerChallenge();

        $this->post(route('login.2fa.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(
            1,
            LoginTwoFactorChallenge::where('user_id', $user->id)->firstOrFail()->attempts
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.login_2fa_failed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_customer_2fa_blocks_after_maximum_attempts(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        LoginTwoFactorChallenge::where('user_id', $user->id)
            ->update(['attempts' => LoginTwoFactorService::MAX_ATTEMPTS]);

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_expired_customer_2fa_code_is_rejected(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        LoginTwoFactorChallenge::where('user_id', $user->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
    }

    public function test_used_customer_2fa_code_cannot_be_reused(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        $this->post(route('login.2fa.verify'), ['code' => $code]);
        $this->post(route('logout'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'customer.logout',
        ]);

        $this->post(route('login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
    }

    public function test_customer_2fa_resend_replaces_old_code_and_obeys_cooldown(): void
    {
        [$user] = $this->startCustomerChallenge();
        $oldCode = '000000';

        LoginTwoFactorChallenge::where('user_id', $user->id)
            ->update(['code_hash' => Hash::make($oldCode)]);

        $this->post(route('login.2fa.resend'))
            ->assertSessionHasErrors('resend');
        Mail::assertQueued(LoginTwoFactorCodeMail::class, 1);

        $this->travel(LoginTwoFactorService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->post(route('login.2fa.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $newCode = Mail::queued(LoginTwoFactorCodeMail::class)->last()->code;

        $this->post(route('login.2fa.verify'), ['code' => $oldCode])
            ->assertSessionHasErrors('code');

        $this->post(route('login.2fa.verify'), ['code' => $newCode])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        Mail::assertQueued(LoginTwoFactorCodeMail::class, 2);
    }

    public function test_arbitrary_pending_user_cannot_target_admin_from_customer_2fa_route(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->withSession([
            'login_2fa' => [
                'user_id' => $admin->id,
                'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
            ],
        ])->post(route('login.2fa.verify'), ['code' => '123456'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_unverified_customer_login_redirects_to_email_verification_without_2fa(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => 'Password123!',
            'is_admin' => false,
        ]);

        $this->post(route('login.submit'), [
            'email' => 'unverified@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertAuthenticatedAs($user);
        Mail::assertNotQueued(LoginTwoFactorCodeMail::class);
    }

    public function test_correct_admin_password_does_not_immediately_authenticate_and_sends_2fa_mail(): void
    {
        [$admin] = $this->startAdminChallenge();

        $this->assertGuest();
        $this->assertDatabaseHas('login_two_factor_challenges', [
            'user_id' => $admin->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
            'attempts' => 0,
        ]);
        Mail::assertQueued(
            LoginTwoFactorCodeMail::class,
            fn (LoginTwoFactorCodeMail $mail) => $mail->hasTo($admin->email)
                && $mail->context === LoginTwoFactorChallenge::CONTEXT_ADMIN
                && Hash::check(
                    $mail->code,
                    LoginTwoFactorChallenge::where('user_id', $admin->id)
                        ->where('context', LoginTwoFactorChallenge::CONTEXT_ADMIN)
                        ->firstOrFail()
                        ->code_hash
                )
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.login_2fa_challenge_initiated',
            'auditable_id' => $admin->id,
        ]);
    }

    public function test_admin_2fa_challenge_renders_as_admin_security_dialog(): void
    {
        $this->startAdminChallenge();

        $this->get(route('admin.login.2fa.show'))
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('Admin security')
            ->assertSee('data-sensitive-code', false);
    }

    public function test_developer_provisioned_admin_does_not_need_customer_email_verification_for_login_2fa(): void
    {
        Mail::fake();

        $admin = User::factory()->unverified()->create([
            'email' => 'unverified-admin-2fa@example.com',
            'password' => 'Password123!',
            'is_admin' => true,
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'unverified-admin-2fa@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.login.2fa.show'));

        $this->assertGuest();
        $this->assertDatabaseHas('login_two_factor_challenges', [
            'user_id' => $admin->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
        ]);
        Mail::assertQueued(LoginTwoFactorCodeMail::class, 1);
    }

    public function test_correct_admin_2fa_code_completes_admin_login_and_logs_success(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_2fa_success']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login']);
    }

    public function test_admin_2fa_rejects_external_intended_redirect(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->replacePendingIntended('https://evil.example/admin');

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_2fa_preserves_safe_local_admin_intended_redirect(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->replacePendingIntended(route('admin.products.index'));

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('admin.products.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_2fa_rejects_customer_intended_redirect(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->replacePendingIntended(route('products.index'));

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_dashboard_is_accessible_after_successful_2fa(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->post(route('admin.login.2fa.verify'), ['code' => $code]);

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_wrong_admin_password_is_rejected_without_starting_2fa(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'wrong-password-admin@example.com',
            'password' => 'Password123!',
            'is_admin' => true,
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'wrong-password-admin@example.com',
            'password' => 'WrongPassword123!',
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $admin->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_failed']);
        Mail::assertNothingQueued();
    }

    public function test_customer_cannot_enter_admin_2fa_flow(): void
    {
        [$customer] = $this->startCustomerChallenge();

        $this->get(route('admin.login.2fa.show'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseHas('login_two_factor_challenges', [
            'user_id' => $customer->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
        ]);
    }

    public function test_pending_customer_2fa_can_be_cancelled_and_clears_challenge_state(): void
    {
        [$customer] = $this->startCustomerChallenge();

        $this->post(route('login.2fa.cancel'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('login_2fa');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $customer->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
        ]);
    }

    public function test_customer_cannot_start_admin_2fa_from_admin_login(): void
    {
        Mail::fake();

        $customer = User::factory()->create([
            'email' => 'not-admin@example.com',
            'password' => 'Password123!',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'not-admin@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $customer->id,
        ]);
        Mail::assertNothingQueued();
    }

    public function test_customer_cannot_complete_admin_challenge(): void
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->withSession([
            'login_2fa' => [
                'user_id' => $customer->id,
                'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
            ],
        ])->post(route('admin.login.2fa.verify'), ['code' => '123456'])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_wrong_admin_2fa_code_fails_and_increments_attempts(): void
    {
        [$admin] = $this->startAdminChallenge();

        $this->post(route('admin.login.2fa.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(
            1,
            LoginTwoFactorChallenge::where('user_id', $admin->id)->firstOrFail()->attempts
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_2fa_failed']);
    }

    public function test_expired_admin_2fa_code_is_rejected(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        LoginTwoFactorChallenge::where('user_id', $admin->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $admin->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_2fa_failed']);
    }

    public function test_admin_2fa_blocks_after_maximum_attempts(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        LoginTwoFactorChallenge::where('user_id', $admin->id)
            ->update(['attempts' => LoginTwoFactorService::MAX_ATTEMPTS]);

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_2fa_failed']);
    }

    public function test_admin_2fa_resend_cooldown_and_replacement(): void
    {
        [$admin] = $this->startAdminChallenge();
        $oldCode = '000000';

        LoginTwoFactorChallenge::where('user_id', $admin->id)
            ->update(['code_hash' => Hash::make($oldCode), 'attempts' => 3]);

        $this->travel(LoginTwoFactorService::RESEND_COOLDOWN_SECONDS - 1)->seconds();

        $this->post(route('admin.login.2fa.resend'))
            ->assertSessionHasErrors('resend');

        Mail::assertQueued(LoginTwoFactorCodeMail::class, 1);
        $this->assertSame(
            3,
            LoginTwoFactorChallenge::where('user_id', $admin->id)->firstOrFail()->attempts
        );

        $this->travel(2)->seconds();

        $this->post(route('admin.login.2fa.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $challenge = LoginTwoFactorChallenge::where('user_id', $admin->id)->firstOrFail();
        $newCode = Mail::queued(LoginTwoFactorCodeMail::class)->last()->code;

        $this->assertSame(0, $challenge->attempts);
        $this->assertTrue($challenge->expires_at->gt(now()->addMinutes(9)));

        $this->post(route('admin.login.2fa.verify'), ['code' => $oldCode])
            ->assertSessionHasErrors('code');

        $this->post(route('admin.login.2fa.verify'), ['code' => $newCode])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        Mail::assertQueued(LoginTwoFactorCodeMail::class, 2);
        Mail::assertQueued(
            LoginTwoFactorCodeMail::class,
            fn (LoginTwoFactorCodeMail $mail) => $mail->hasTo($admin->email)
                && $mail->context === LoginTwoFactorChallenge::CONTEXT_ADMIN
        );
    }

    public function test_used_admin_2fa_code_cannot_be_replayed(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));

        $this->post(route('admin.login.2fa.verify'), ['code' => $code])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $admin->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
        ]);
    }

    public function test_admin_route_remains_inaccessible_before_successful_2fa(): void
    {
        $this->startAdminChallenge();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_pending_admin_2fa_can_be_cancelled_and_clears_challenge_state(): void
    {
        [$admin] = $this->startAdminChallenge();

        $this->post(route('admin.login.2fa.cancel'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionMissing('login_2fa');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $admin->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
        ]);
    }

    public function test_logout_invalidates_admin_authentication_after_2fa(): void
    {
        [$admin, $code] = $this->startAdminChallenge();

        $this->post(route('admin.login.2fa.verify'), ['code' => $code]);
        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_login_2fa_code_is_hashed_at_rest(): void
    {
        [$user, $code] = $this->startCustomerChallenge();

        $challenge = LoginTwoFactorChallenge::where('user_id', $user->id)
            ->firstOrFail();

        $this->assertNotSame($code, $challenge->code_hash);
        $this->assertTrue(Hash::check($code, $challenge->code_hash));
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function startCustomerChallenge(): array
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'customer-2fa@example.com',
            'password' => 'Password123!',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->post(route('login.submit'), [
            'email' => 'customer-2fa@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('login.2fa.show'));

        $mail = Mail::queued(LoginTwoFactorCodeMail::class)->first();
        $this->assertInstanceOf(LoginTwoFactorCodeMail::class, $mail);

        return [$user, $mail->code];
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function startAdminChallenge(): array
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'admin-2fa@example.com',
            'password' => 'Password123!',
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin-2fa@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.login.2fa.show'));

        $mail = Mail::queued(LoginTwoFactorCodeMail::class)->first();
        $this->assertInstanceOf(LoginTwoFactorCodeMail::class, $mail);

        return [$admin, $mail->code];
    }

    private function replacePendingIntended(string $intended): void
    {
        $pending = session('login_2fa');
        $this->assertIsArray($pending);

        $pending['intended'] = $intended;

        session(['login_2fa' => $pending]);
    }
}
