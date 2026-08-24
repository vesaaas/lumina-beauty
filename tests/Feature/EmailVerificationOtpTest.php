<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationOtpMail;
use App\Models\User;
use App\Services\EmailVerificationOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_otp_record_and_sends_mail(): void
    {
        Mail::fake();

        $this->post(route('register.submit'), $this->registrationPayload())
            ->assertRedirect(route('verification.otp.show'));

        $user = User::where('email', 'otp-customer@example.com')->firstOrFail();

        $this->assertDatabaseHas('email_verification_otps', [
            'user_id' => $user->id,
            'attempts' => 0,
        ]);

        Mail::assertQueued(
            EmailVerificationOtpMail::class,
            fn (EmailVerificationOtpMail $mail) => $mail->hasTo($user->email)
                && preg_match('/^\d{6}$/', $mail->code) === 1
                && $mail->afterCommit === true
        );
    }

    public function test_registration_redirect_renders_otp_page(): void
    {
        Mail::fake();

        $this->followingRedirects()
            ->post(route('register.submit'), $this->registrationPayload())
            ->assertOk()
            ->assertSee('Verify your email')
            ->assertSee('ot**********@example.com');

        Mail::assertQueued(EmailVerificationOtpMail::class, 1);
    }

    public function test_auth_pages_render_with_shared_storefront_layout_data(): void
    {
        $viewData = ['errors' => new ViewErrorBag()];

        $this->view('auth.login', $viewData)->assertSee('Login');
        $this->view('auth.register', $viewData)->assertSee('Register');
        $this->view('auth.verify-email-otp', [
            ...$viewData,
            'email' => 'otp-customer@example.com',
            'resendCooldownSeconds' => 0,
        ])->assertSee('Verify your email');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot Password?');

        $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'otp-customer@example.com',
        ]))
            ->assertOk()
            ->assertSee('Reset Password');
    }

    public function test_otp_is_stored_hashed_and_never_plaintext(): void
    {
        [$user, $code] = $this->createOtpForUser();

        $otp = $user->emailVerificationOtp()->firstOrFail();

        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(Hash::check($code, $otp->code_hash));
    }

    public function test_otp_expires_after_10_minutes(): void
    {
        [$user, $code] = $this->createOtpForUser();

        $this->travel(11)->minutes();

        $this->actingAs($user)
            ->post(route('verification.otp.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('email_verification_otps', [
            'user_id' => $user->id,
        ]);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registration.email_verification_expired',
        ]);
    }

    public function test_correct_otp_verifies_user_and_deletes_otp(): void
    {
        [$user, $code] = $this->createOtpForUser();

        $this->actingAs($user)
            ->post(route('verification.otp.verify'), ['code' => $code])
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_otps', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registration.email_verified',
        ]);
    }

    public function test_incorrect_otp_increments_attempts(): void
    {
        [$user] = $this->createOtpForUser();

        $this->actingAs($user)
            ->post(route('verification.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertSame(
            1,
            $user->emailVerificationOtp()->firstOrFail()->attempts
        );
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registration.email_verification_failed',
        ]);
    }

    public function test_verification_is_blocked_after_maximum_attempts(): void
    {
        [$user, $code] = $this->createOtpForUser();

        $user->emailVerificationOtp()->update(['attempts' => 5]);

        $this->actingAs($user)
            ->post(route('verification.otp.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertSame(
            5,
            $user->emailVerificationOtp()->firstOrFail()->attempts
        );
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registration.email_verification_locked',
        ]);
    }

    public function test_resend_replaces_otp_resets_attempts_and_expiry_and_sends_mail(): void
    {
        [$user] = $this->createOtpForUser();

        $originalOtp = $user->emailVerificationOtp()->firstOrFail();
        $user->emailVerificationOtp()->update(['attempts' => 4]);

        $this->travel(EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->actingAs($user)
            ->post(route('verification.otp.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $newOtp = $user->emailVerificationOtp()->firstOrFail();

        $this->assertNotSame($originalOtp->code_hash, $newOtp->code_hash);
        $this->assertSame(0, $newOtp->attempts);
        $this->assertTrue($newOtp->expires_at->greaterThan($originalOtp->expires_at));
        $this->assertTrue($newOtp->last_sent_at->greaterThan($originalOtp->last_sent_at));
        Mail::assertQueued(EmailVerificationOtpMail::class, 2);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registration.email_verification_otp_resent',
        ]);
    }

    public function test_old_otp_fails_after_resend(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        $oldCode = '000000';

        $user->emailVerificationOtp()->create([
            'code_hash' => Hash::make($oldCode),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subSeconds(EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS + 1),
        ]);

        $this->assertTrue(Hash::check(
            $oldCode,
            $user->emailVerificationOtp()->firstOrFail()->code_hash
        ));

        $this->actingAs($user)
            ->post(route('verification.otp.resend'))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('verification.otp.verify'), ['code' => $oldCode])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        Mail::assertQueued(EmailVerificationOtpMail::class, 1);
    }

    public function test_resend_before_cooldown_expires_is_rejected_without_replacing_otp_or_sending_mail(): void
    {
        [$user] = $this->createOtpForUser();

        $originalOtp = $user->emailVerificationOtp()->firstOrFail();

        $this->travel(EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS - 1)->seconds();

        $this->actingAs($user)
            ->post(route('verification.otp.resend'))
            ->assertSessionHasErrors('resend');

        $currentOtp = $user->emailVerificationOtp()->firstOrFail();

        $this->assertSame($originalOtp->code_hash, $currentOtp->code_hash);
        $this->assertTrue($originalOtp->expires_at->equalTo($currentOtp->expires_at));
        $this->assertTrue($originalOtp->last_sent_at->equalTo($currentOtp->last_sent_at));
        Mail::assertQueued(EmailVerificationOtpMail::class, 1);
    }

    public function test_resend_after_cooldown_expires_is_accepted(): void
    {
        [$user] = $this->createOtpForUser();

        $this->travel(EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->actingAs($user)
            ->post(route('verification.otp.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertQueued(EmailVerificationOtpMail::class, 2);
    }

    public function test_verified_user_cannot_open_otp_page_or_resend(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('verification.otp.show'))
            ->assertRedirect(route('home'));

        $this->actingAs($user)
            ->post(route('verification.otp.resend'))
            ->assertRedirect(route('home'));

        Mail::assertNothingQueued();
        $this->assertDatabaseMissing('email_verification_otps', [
            'user_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_otp_endpoints(): void
    {
        $this->get(route('verification.otp.show'))
            ->assertRedirect(route('login'));

        $this->post(route('verification.otp.verify'), ['code' => '123456'])
            ->assertRedirect(route('login'));

        $this->post(route('verification.otp.resend'))
            ->assertRedirect(route('login'));
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function createOtpForUser(): array
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'otp-customer@example.com',
        ]);

        app(EmailVerificationOtpService::class)->generateAndSend($user);

        $mail = Mail::queued(EmailVerificationOtpMail::class)->first();

        $this->assertInstanceOf(EmailVerificationOtpMail::class, $mail);

        return [$user->refresh(), $mail->code];
    }

    /**
     * @return array<string, string>
     */
    private function registrationPayload(): array
    {
        return [
            'first_name' => 'Otp',
            'last_name' => 'Customer',
            'email' => 'otp-customer@example.com',
            'phone' => '+383 44 111 222',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
    }
}
