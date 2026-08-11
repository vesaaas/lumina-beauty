<?php

namespace Tests\Feature;

use App\Mail\GuestCheckoutOtpMail;
use App\Mail\OrderStatusNotification;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GuestCheckoutOtp;
use App\Models\Order;
use App\Models\Product;
use App\Services\GuestCheckoutOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestCheckoutOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_order_is_not_finalized_before_email_verification(): void
    {
        Mail::fake();
        $product = $this->product();

        $this->withSession(['guest_cart' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('checkout.guest.otp.show'));

        $this->assertSame(0, Order::count());
        $this->assertDatabaseHas('guest_checkout_otps', [
            'email' => 'guest@example.com',
            'attempts' => 0,
        ]);
        Mail::assertQueued(
            GuestCheckoutOtpMail::class,
            fn (GuestCheckoutOtpMail $mail) => $mail->hasTo('guest@example.com')
                && Hash::check(
                    $mail->code,
                    GuestCheckoutOtp::where('email', 'guest@example.com')
                        ->firstOrFail()
                        ->code_hash
                )
        );
        Mail::assertNotSent(OrderStatusNotification::class);
    }

    public function test_correct_guest_checkout_otp_completes_intended_order(): void
    {
        [$product, $code] = $this->startGuestCheckoutChallenge();

        $this->post(route('checkout.guest.otp.verify'), ['code' => $code])
            ->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame('guest@example.com', $order->customer_email);
        $this->assertNull($order->user_id);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseMissing('guest_checkout_otps', [
            'email' => 'guest@example.com',
        ]);
        $this->assertSame([], session('guest_cart', []));
        Mail::assertSent(OrderStatusNotification::class, 1);
    }

    public function test_wrong_guest_checkout_otp_is_rejected(): void
    {
        $this->startGuestCheckoutChallenge();

        $this->post(route('checkout.guest.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, GuestCheckoutOtp::firstOrFail()->attempts);
    }

    public function test_expired_guest_checkout_otp_is_rejected(): void
    {
        $this->startGuestCheckoutChallenge();

        GuestCheckoutOtp::firstOrFail()->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->post(route('checkout.guest.otp.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, GuestCheckoutOtp::count());
    }

    public function test_guest_checkout_otp_resend_protection_and_old_code_invalidation(): void
    {
        [, $oldCode] = $this->startGuestCheckoutChallenge();

        GuestCheckoutOtp::firstOrFail()->update([
            'code_hash' => Hash::make('000000'),
        ]);
        $oldCode = '000000';

        $this->post(route('checkout.guest.otp.resend'))
            ->assertSessionHasErrors('resend');
        Mail::assertQueued(GuestCheckoutOtpMail::class, 1);

        $this->travel(GuestCheckoutOtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->post(route('checkout.guest.otp.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $newCode = Mail::queued(GuestCheckoutOtpMail::class)->last()->code;

        $this->post(route('checkout.guest.otp.verify'), ['code' => $oldCode])
            ->assertSessionHasErrors('code');

        $this->post(route('checkout.guest.otp.verify'), ['code' => $newCode])
            ->assertRedirect();

        $this->assertSame(1, Order::count());
        Mail::assertQueued(GuestCheckoutOtpMail::class, 2);
    }

    public function test_arbitrary_guest_cannot_verify_another_guest_checkout(): void
    {
        $product = $this->product();

        $otp = GuestCheckoutOtp::create([
            'session_id' => 'another-session',
            'email' => 'guest@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $this->withSession([
            'guest_cart' => [$product->id => 1],
            'guest_checkout.pending' => [
                'attributes' => $this->checkoutPayload(),
                'otp_id' => $otp->id + 1000,
            ],
        ])->post(route('checkout.guest.otp.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, Order::count());
    }

    public function test_guest_checkout_otp_cannot_be_verified_from_another_session(): void
    {
        $product = $this->product();

        $otp = GuestCheckoutOtp::create([
            'session_id' => 'owner-session-id',
            'email' => 'guest@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subMinute(),
        ]);

        $this->withSession([
            'guest_cart' => [$product->id => 1],
            'guest_checkout.pending' => [
                'attributes' => $this->checkoutPayload(),
                'otp_id' => $otp->id,
            ],
        ])->post(route('checkout.guest.otp.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, Order::count());
        $this->assertDatabaseHas('guest_checkout_otps', [
            'id' => $otp->id,
            'session_id' => 'owner-session-id',
            'attempts' => 0,
        ]);
    }

    public function test_guest_checkout_otp_cannot_be_resent_from_another_session(): void
    {
        Mail::fake();

        $otp = GuestCheckoutOtp::create([
            'session_id' => 'owner-session-id',
            'email' => 'guest@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subMinute(),
        ]);

        $this->withSession([
            'guest_checkout.pending' => [
                'attributes' => $this->checkoutPayload(),
                'otp_id' => $otp->id,
            ],
        ])->post(route('checkout.guest.otp.resend'))
            ->assertSessionHasErrors('resend');

        Mail::assertNothingQueued();
        $this->assertDatabaseHas('guest_checkout_otps', [
            'id' => $otp->id,
            'session_id' => 'owner-session-id',
        ]);
    }

    /**
     * @return array{0: Product, 1: string}
     */
    private function startGuestCheckoutChallenge(): array
    {
        Mail::fake();
        $product = $this->product();

        $this->withSession(['guest_cart' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('checkout.guest.otp.show'));

        $mail = Mail::queued(GuestCheckoutOtpMail::class)->first();
        $this->assertInstanceOf(GuestCheckoutOtpMail::class, $mail);
        $this->continueGuestCheckoutSession();

        return [$product, $mail->code];
    }

    private function continueGuestCheckoutSession(): void
    {
        $this->withCookie(
            config('session.cookie'),
            GuestCheckoutOtp::firstOrFail()->session_id
        );
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Skin Care', 'slug' => 'skin-care']);
        $brand = Brand::create(['name' => 'Lumina', 'slug' => 'lumina']);

        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Guest Serum',
            'slug' => 'guest-serum',
            'description' => 'A checkout product.',
            'price' => 50,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Guest Customer',
            'customer_email' => 'guest@example.com',
            'customer_phone' => '+38344111222',
            'shipping_address' => 'Mother Teresa Boulevard 10',
            'shipping_city' => 'Prishtina',
            'shipping_country' => 'Kosovo',
        ];
    }
}
