<?php

namespace Tests\Feature;

use App\Mail\OrderStatusNotification;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_order_is_saved_with_items(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Perfume',
            'slug' => 'perfume',
        ]);
        $brand = Brand::create([
            'name' => 'Lumina',
            'slug' => 'lumina',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Velvet Rose Eau de Parfum',
            'slug' => 'velvet-rose-eau-de-parfum',
            'description' => 'A polished floral scent.',
            'price' => 120,
            'sale_price' => 95,
            'stock' => 8,
            'is_active' => true,
        ]);

        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => 'Elira Customer',
            'customer_email' => 'elira@example.com',
            'customer_phone' => '+38344111222',
            'shipping_address' => 'Mother Teresa Boulevard 10',
            'shipping_city' => 'Prishtina',
            'shipping_country' => 'Kosovo',
        ]);

        $order = Order::first();

        $response->assertRedirect(route('orders.thank-you', $order));
        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertStringStartsWith('LB-'.now()->format('Ymd').'-', $order->order_number);
        $this->assertSame('190.00', $order->total);
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(0, CartItem::count());
        $this->assertSame(6, $product->fresh()->stock);
        Mail::assertSent(OrderStatusNotification::class, fn (OrderStatusNotification $mail) => $mail->hasTo('elira@example.com')
            && $mail->notificationType === 'pending'
            && $mail->order->is($order));
    }

    public function test_processing_and_completed_status_changes_email_customer(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::create([
            'order_number' => 'LB-TEST-STATUS',
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

        $order->items()->create([
            'product_name' => 'Lumina Serum',
            'brand_name' => 'Lumina',
            'category_name' => 'Skin Care',
            'unit_price' => 32,
            'quantity' => 2,
            'line_total' => 64,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'processing'])
            ->assertRedirect();

        Mail::assertSent(OrderStatusNotification::class, fn (OrderStatusNotification $mail) => $mail->hasTo('mira@example.com')
            && $mail->notificationType === 'processing');

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'completed'])
            ->assertRedirect();

        Mail::assertSent(OrderStatusNotification::class, fn (OrderStatusNotification $mail) => $mail->hasTo('mira@example.com')
            && $mail->notificationType === 'completed');
    }
}
