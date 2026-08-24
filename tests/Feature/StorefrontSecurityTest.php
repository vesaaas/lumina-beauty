<?php

namespace Tests\Feature;

use App\Mail\GuestCheckoutOtpMail;
use App\Mail\StorefrontPageMessage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GuestCheckoutOtp;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StorefrontSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_view_own_order_confirmation(): void
    {
        $user = User::factory()->create();
        $order = $this->order(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('orders.thank-you', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_customer_cannot_view_another_customers_order_confirmation(): void
    {
        $owner = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $order = $this->order(['user_id' => $owner->id]);

        $this->actingAs($otherCustomer)
            ->get(route('orders.thank-you', $order))
            ->assertForbidden();
    }

    public function test_admin_policy_can_view_customer_order_confirmation(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order(['user_id' => $customer->id]);

        $this->actingAs($admin)
            ->get(route('orders.thank-you', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_logout_invalidates_customer_session_and_protected_routes_redirect(): void
    {
        $user = User::factory()->create();
        $order = $this->order(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('orders.thank-you', $order))
            ->assertOk();

        $this->assertStringContainsString(
            'no-store',
            $response->headers->get('Cache-Control')
        );

        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();

        $this->get(route('orders.thank-you', $order))
            ->assertForbidden();
    }

    public function test_authenticated_storefront_response_receives_private_no_cache_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_legitimate_guest_can_view_newly_created_order_confirmation(): void
    {
        Mail::fake();
        $product = $this->product();

        $this->withSession([
            'guest_cart' => [$product->id => 1],
        ])->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('checkout.guest.otp.show'));

        $code = Mail::queued(GuestCheckoutOtpMail::class)->first()->code;

        $this->withCookie(
            config('session.cookie'),
            GuestCheckoutOtp::firstOrFail()->session_id
        );

        $this->post(route('checkout.guest.otp.verify'), ['code' => $code])
            ->assertRedirect();

        $order = Order::firstOrFail();

        $this->get(route('orders.thank-you', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_random_guest_cannot_view_arbitrary_order_confirmation(): void
    {
        $order = $this->order();

        $this->get(route('orders.thank-you', $order))
            ->assertForbidden();
    }

    public function test_contact_honeypot_submission_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'topic' => 'Spam',
            'message' => 'This message is long enough to pass normal validation.',
            'website' => 'https://spam.example',
            'form_started_at' => time() - 10,
        ])->assertSessionHasErrors('website', null, 'contact');

        Mail::assertNothingSent();
    }

    public function test_contact_timing_submission_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), [
            'name' => 'Fast Bot',
            'email' => 'bot@example.com',
            'topic' => 'Spam',
            'message' => 'This message is long enough to pass normal validation.',
            'website' => '',
            'form_started_at' => time(),
        ])->assertSessionHasErrors('form_started_at', null, 'contact');

        Mail::assertNothingSent();
    }

    public function test_contact_form_accepts_legitimate_submission(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), [
            'name' => 'Mira',
            'email' => 'mira@example.com',
            'topic' => 'Product question',
            'message' => 'Can you help me choose the right serum?',
            'website' => '',
            'form_started_at' => time() - 10,
        ])->assertSessionHas('contact_status');

        Mail::assertSent(StorefrontPageMessage::class);
    }

    public function test_about_honeypot_submission_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('about.send'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'This message is long enough to pass normal validation.',
            'website' => 'https://spam.example',
            'form_started_at' => time() - 10,
        ])->assertSessionHasErrors('website', null, 'about');

        Mail::assertNothingSent();
    }

    public function test_about_timing_submission_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('about.send'), [
            'name' => 'Fast Bot',
            'email' => 'bot@example.com',
            'message' => 'This message is long enough to pass normal validation.',
            'website' => '',
            'form_started_at' => time(),
        ])->assertSessionHasErrors('form_started_at', null, 'about');

        Mail::assertNothingSent();
    }

    public function test_about_form_accepts_legitimate_submission(): void
    {
        Mail::fake();

        $this->post(route('about.send'), [
            'name' => 'Mira',
            'email' => 'mira@example.com',
            'message' => 'Can you tell me more about Lumina Beauty?',
            'website' => '',
            'form_started_at' => time() - 10,
        ])->assertSessionHas('about_status');

        Mail::assertSent(StorefrontPageMessage::class);
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Skin Care', 'slug' => 'skin-care']);
        $brand = Brand::create(['name' => 'Lumina', 'slug' => 'lumina']);

        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Lumina Serum',
            'slug' => 'lumina-serum',
            'description' => 'A polished serum.',
            'price' => 32,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    private function order(array $attributes = []): Order
    {
        return Order::create($attributes + [
            'order_number' => 'LB-TEST-'.uniqid(),
            'customer_name' => 'Mira Customer',
            'customer_email' => 'mira@example.com',
            'customer_phone' => '+38344111999',
            'shipping_address' => 'Beauty Street 2',
            'shipping_city' => 'Prishtina',
            'shipping_country' => 'Kosovo',
            'status' => 'pending',
            'subtotal' => 64,
            'discount_total' => 0,
            'total' => 64,
        ]);
    }

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
