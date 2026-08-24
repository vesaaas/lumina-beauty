<?php

namespace Tests\Feature;

use App\Models\GuestCheckoutOtp;
use App\Models\LoginTwoFactorChallenge;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_authentication_and_message_routes_are_throttled(): void
    {
        Mail::fake();
        Notification::fake();

        $this->assertRouteThrottles('post', route('login.submit'), [
            'email' => 'missing@example.com',
            'password' => 'WrongPassword123!',
        ], 5, '10.10.0.1');

        $this->assertRouteThrottles('post', route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'WrongPassword123!',
        ], 5, '10.10.0.2');

        $this->assertRouteThrottles('post', route('register.submit'), [
            'first_name' => 'Rate',
            'last_name' => 'Limited',
            'email' => 'rate@example.com',
            'phone' => '+383 44 111 222',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ], 3, '10.10.0.3');

        $this->assertRouteThrottles('post', route('password.email'), [
            'email' => 'unknown@example.com',
        ], 3, '10.10.0.4');

        $messagePayload = [
            'name' => 'Mira',
            'email' => 'mira@example.com',
            'topic' => 'Question',
            'message' => 'This message is long enough to pass normal validation.',
            'website' => '',
            'form_started_at' => time() - 10,
        ];

        $this->assertRouteThrottles('post', route('contact.send'), $messagePayload, 3, '10.10.0.5');

        unset($messagePayload['topic']);
        $this->assertRouteThrottles('post', route('about.send'), $messagePayload, 3, '10.10.0.6');
    }

    public function test_otp_and_2fa_routes_are_throttled(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->assertRouteThrottles('post', route('verification.otp.verify'), [
            'code' => '000000',
        ], 5, '10.10.1.1');

        $resendUser = User::factory()->unverified()->create();
        $this->actingAs($resendUser);

        $this->assertRouteThrottles('post', route('verification.otp.resend'), [], 3, '10.10.1.2');

        auth()->logout();
        $this->flushSession();

        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        LoginTwoFactorChallenge::create([
            'user_id' => $verifiedUser->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);
        $this->withSession([
            'login_2fa' => [
                'user_id' => $verifiedUser->id,
                'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
            ],
        ]);

        $this->assertRouteThrottles('post', route('login.2fa.verify'), [
            'code' => '000000',
        ], 5, '10.10.1.3');

        $this->assertRouteThrottles('post', route('login.2fa.resend'), [], 3, '10.10.1.4');

        $otp = GuestCheckoutOtp::create([
            'session_id' => 'guest-session',
            'email' => 'guest@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $this->withCookie(config('session.cookie'), 'guest-session')
            ->withSession([
                'guest_checkout.pending' => [
                    'attributes' => [
                        'customer_name' => 'Guest Customer',
                        'customer_email' => 'guest@example.com',
                        'customer_phone' => '+38344111222',
                        'shipping_address' => 'Mother Teresa Boulevard 10',
                        'shipping_city' => 'Prishtina',
                        'shipping_country' => 'Kosovo',
                    ],
                    'otp_id' => $otp->id,
                ],
            ]);

        $this->assertRouteThrottles('post', route('checkout.guest.otp.verify'), [
            'code' => '000000',
        ], 5, '10.10.1.5');

        $this->assertRouteThrottles('post', route('checkout.guest.otp.resend'), [], 3, '10.10.1.6');
    }

    public function test_security_headers_are_present_with_report_only_csp_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_csp_can_be_enforced_by_configuration(): void
    {
        config(['security.csp.enforce' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_hsts_is_only_sent_for_enabled_secure_production_requests(): void
    {
        config([
            'security.hsts.enabled' => true,
            'security.hsts.max_age' => 123,
            'security.hsts.include_subdomains' => true,
            'security.hsts.preload' => false,
        ]);

        $this->get(route('home'))
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->app->detectEnvironment(fn () => 'production');

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get(route('home'))
            ->assertHeader('Strict-Transport-Security', 'max-age=123; includeSubDomains');
    }

    public function test_admin_login_requests_do_not_consume_admin_2fa_verify_quota(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.1'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('admin.login.submit'), [
                    'email' => 'admin@example.com',
                    'password' => 'WrongPassword123!',
                ])
                ->assertStatus(422);
        }

        $this->beginTwoFactorChallenge($admin, LoginTwoFactorChallenge::CONTEXT_ADMIN);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.1'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.login.2fa.verify'), ['code' => '000000'])
            ->assertStatus(422);
    }

    public function test_two_wrong_admin_2fa_attempts_do_not_trigger_throttle(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->beginTwoFactorChallenge($admin, LoginTwoFactorChallenge::CONTEXT_ADMIN);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.6'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('admin.login.2fa.verify'), ['code' => '000000'])
                ->assertStatus(422);
        }
    }

    public function test_customer_login_requests_do_not_consume_customer_2fa_verify_quota(): void
    {
        Mail::fake();

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.2'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('login.submit'), [
                    'email' => 'customer@example.com',
                    'password' => 'WrongPassword123!',
                ])
                ->assertStatus(422);
        }

        $this->beginTwoFactorChallenge($customer, LoginTwoFactorChallenge::CONTEXT_CUSTOMER);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.2'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('login.2fa.verify'), ['code' => '000000'])
            ->assertStatus(422);
    }

    public function test_admin_and_customer_2fa_verify_buckets_are_isolated(): void
    {
        Mail::fake();

        $customer = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->beginTwoFactorChallenge($customer, LoginTwoFactorChallenge::CONTEXT_CUSTOMER);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.3'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('login.2fa.verify'), ['code' => '000000'])
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.3'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('login.2fa.verify'), ['code' => '000000'])
            ->assertStatus(429);

        $this->beginTwoFactorChallenge($admin, LoginTwoFactorChallenge::CONTEXT_ADMIN);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.3'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.login.2fa.verify'), ['code' => '000000'])
            ->assertStatus(422);
    }

    public function test_2fa_verify_and_resend_buckets_are_isolated(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->beginTwoFactorChallenge($admin, LoginTwoFactorChallenge::CONTEXT_ADMIN);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.4'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('admin.login.2fa.resend'))
                ->assertStatus($attempt === 1 ? 302 : 422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.4'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.login.2fa.resend'))
            ->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.4'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.login.2fa.verify'), ['code' => '000000'])
            ->assertStatus(422);
    }

    public function test_add_to_cart_is_not_throttled_by_auth_rate_limiters(): void
    {
        Mail::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.5'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('admin.login.submit'), [
                    'email' => 'blocked-admin@example.com',
                    'password' => 'WrongPassword123!',
                ])
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.5'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.login.submit'), [
                'email' => 'blocked-admin@example.com',
                'password' => 'WrongPassword123!',
            ])
            ->assertStatus(429);

        $product = $this->createPurchasableProduct();

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.5'])
            ->post(route('cart.add', $product), ['quantity' => 1])
            ->assertRedirect();

        $this->assertSame([1 => 1], session('guest_cart'));
    }

    public function test_contact_and_about_first_legitimate_submissions_are_not_affected_by_auth_throttling(): void
    {
        Mail::fake();
        Notification::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.7'])
                ->withHeaders(['Accept' => 'application/json'])
                ->post(route('login.submit'), [
                    'email' => 'blocked-customer@example.com',
                    'password' => 'WrongPassword123!',
                ])
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.7'])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('login.submit'), [
                'email' => 'blocked-customer@example.com',
                'password' => 'WrongPassword123!',
            ])
            ->assertStatus(429);

        $contactPayload = [
            'name' => 'Mira',
            'email' => 'mira-contact@example.com',
            'topic' => 'Question',
            'message' => 'This message is long enough to pass normal validation.',
            'website' => '',
            'form_started_at' => time() - 10,
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.7'])
            ->post(route('contact.send'), $contactPayload)
            ->assertRedirect();

        unset($contactPayload['topic']);
        $contactPayload['email'] = 'mira-about@example.com';

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.0.7'])
            ->post(route('about.send'), $contactPayload)
            ->assertRedirect();
    }

    private function assertRouteThrottles(
        string $method,
        string $uri,
        array $payload,
        int $limit,
        string $ip
    ): void {
        for ($attempt = 1; $attempt <= $limit; $attempt++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->withHeaders(['Accept' => 'application/json'])
                ->{$method}($uri, $payload);

            $this->assertNotSame(429, $response->getStatusCode());
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['Accept' => 'application/json'])
            ->{$method}($uri, $payload)
            ->assertStatus(429);
    }

    private function beginTwoFactorChallenge(User $user, string $context): void
    {
        LoginTwoFactorChallenge::updateOrCreate(
            [
                'user_id' => $user->id,
                'context' => $context,
            ],
            [
                'code_hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'last_sent_at' => now()->subMinute(),
            ]
        );

        $this->withSession([
            'login_2fa' => [
                'user_id' => $user->id,
                'context' => $context,
            ],
        ]);
    }

    private function createPurchasableProduct(): Product
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
        $brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Test Cart Product',
            'slug' => 'test-cart-product',
            'description' => 'A product used to verify cart throttling isolation.',
            'price' => 25,
            'stock' => 10,
            'is_active' => true,
        ]);
    }
}
