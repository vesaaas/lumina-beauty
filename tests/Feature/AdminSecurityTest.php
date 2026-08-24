<?php

namespace Tests\Feature;

use App\Mail\OrderStatusNotification;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_admin_dashboard_request_redirects_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_access_dashboard_with_no_cache_headers(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertAdminNoCacheHeaders($response);
    }

    public function test_logout_invalidates_admin_session_and_admin_dashboard_redirects_to_admin_login(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();

        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
        $this->assertAdminNoCacheHeaders($response);
    }

    public function test_wrong_password_prevents_brand_deletion(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Unused', 'slug' => 'unused']);

        $this->actingAs($admin)
            ->delete(route('admin.brands.destroy', $brand), ['password' => 'WrongPassword123!'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_correct_password_deletes_unused_brand_and_logs_it(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Unused', 'slug' => 'unused']);

        $this->actingAs($admin)
            ->delete(route('admin.brands.destroy', $brand), ['password' => 'Password123!'])
            ->assertSessionHas('admin_status', 'Brand deleted.');

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'brand.deleted',
            'auditable_type' => Brand::class,
            'auditable_id' => $brand->id,
        ]);
    }

    public function test_brand_containing_products_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        [, $brand] = $this->product();

        $this->actingAs($admin)
            ->delete(route('admin.brands.destroy', $brand), ['password' => 'Password123!'])
            ->assertSessionHasErrors('brand');

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_wrong_password_prevents_category_deletion(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Unused', 'slug' => 'unused']);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category), ['password' => 'WrongPassword123!'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_correct_password_deletes_unused_category_and_logs_it(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Unused', 'slug' => 'unused']);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category), ['password' => 'Password123!'])
            ->assertSessionHas('admin_status', 'Category deleted.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'category.deleted',
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
        ]);
    }

    public function test_category_containing_products_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        [$category] = $this->product();

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category), ['password' => 'Password123!'])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_wrong_password_prevents_category_creation(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Fragrance',
            'description' => 'Fine fragrances.',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertSessionMissing('_old_input.password');
        $this->assertDatabaseMissing('categories', ['name' => 'Fragrance']);
    }

    public function test_correct_password_allows_category_creation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Fragrance',
            'description' => 'Fine fragrances.',
            'password' => 'Password123!',
        ])->assertSessionHas('admin_status', 'Category created.');

        $this->assertDatabaseHas('categories', ['name' => 'Fragrance']);
    }

    public function test_wrong_password_prevents_category_update(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Skin', 'slug' => 'skin']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Updated Skin',
            'description' => 'Updated.',
            'password' => 'WrongPassword123!',
        ])->assertSessionHasErrors('password');

        $this->assertSame('Skin', $category->fresh()->name);
    }

    public function test_correct_password_allows_category_update(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Skin', 'slug' => 'skin']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Updated Skin',
            'description' => 'Updated.',
            'password' => 'Password123!',
        ])->assertSessionHas('admin_status', 'Category updated.');

        $this->assertSame('Updated Skin', $category->fresh()->name);
    }

    public function test_wrong_password_prevents_brand_creation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.brands.store'), [
            'name' => 'Aster',
            'description' => 'Aster brand.',
            'password' => 'WrongPassword123!',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('brands', ['name' => 'Aster']);
    }

    public function test_correct_password_allows_brand_creation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.brands.store'), [
            'name' => 'Aster',
            'description' => 'Aster brand.',
            'password' => 'Password123!',
        ])->assertSessionHas('admin_status', 'Brand created.');

        $this->assertDatabaseHas('brands', ['name' => 'Aster']);
    }

    public function test_wrong_password_prevents_brand_update(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Aster', 'slug' => 'aster']);

        $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
            'name' => 'Aster Updated',
            'description' => 'Updated.',
            'password' => 'WrongPassword123!',
        ])->assertSessionHasErrors('password');

        $this->assertSame('Aster', $brand->fresh()->name);
    }

    public function test_correct_password_allows_brand_update(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Aster', 'slug' => 'aster']);

        $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
            'name' => 'Aster Updated',
            'description' => 'Updated.',
            'password' => 'Password123!',
        ])->assertSessionHas('admin_status', 'Brand updated.');

        $this->assertSame('Aster Updated', $brand->fresh()->name);
    }

    public function test_wrong_password_prevents_product_creation(): void
    {
        $admin = $this->admin();
        [$category, $brand] = $this->catalog();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->productPayload($category, $brand, [
            'password' => 'WrongPassword123!',
        ]))->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('products', ['name' => 'Protected Serum']);
    }

    public function test_correct_password_allows_product_creation(): void
    {
        $admin = $this->admin();
        [$category, $brand] = $this->catalog();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->productPayload($category, $brand, [
            'password' => 'Password123!',
        ]))->assertSessionHas('admin_status', 'Product created.');

        $this->assertDatabaseHas('products', ['name' => 'Protected Serum']);
    }

    public function test_wrong_password_prevents_product_update(): void
    {
        $admin = $this->admin();
        [$category, $brand, $product] = $this->product();

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->productPayload($category, $brand, [
            'name' => 'Changed Serum',
            'password' => 'WrongPassword123!',
        ]))->assertSessionHasErrors('password');

        $this->assertSame('Lumina Serum', $product->fresh()->name);
    }

    public function test_correct_password_allows_product_update(): void
    {
        $admin = $this->admin();
        [$category, $brand, $product] = $this->product();

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->productPayload($category, $brand, [
            'name' => 'Changed Serum',
            'password' => 'Password123!',
        ]))->assertSessionHas('admin_status', 'Product updated.');

        $this->assertSame('Changed Serum', $product->fresh()->name);
    }

    public function test_allowed_order_status_transitions(): void
    {
        foreach ([
            ['pending', 'processing'],
            ['pending', 'cancelled'],
            ['processing', 'completed'],
            ['processing', 'cancelled'],
        ] as [$from, $to]) {
            Mail::fake();
            $admin = $this->admin();
            $order = $this->order($from);

            $this->actingAs($admin)
                ->patch(route('admin.orders.update', $order), [
                    'status' => $to,
                    'password' => 'Password123!',
                ])
                ->assertSessionHas('admin_status', 'Order status updated.');

            $this->assertSame($to, $order->fresh()->status);
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'order.status_updated',
                'auditable_id' => $order->id,
            ]);
        }
    }

    public function test_forbidden_order_status_transitions_are_rejected(): void
    {
        foreach ([
            ['processing', 'pending'],
            ['completed', 'processing'],
            ['completed', 'cancelled'],
            ['cancelled', 'processing'],
            ['cancelled', 'completed'],
        ] as [$from, $to]) {
            Mail::fake();
            $admin = $this->admin();
            $order = $this->order($from);

            $this->actingAs($admin)
                ->patch(route('admin.orders.update', $order), [
                    'status' => $to,
                    'password' => 'Password123!',
                ])
                ->assertSessionHasErrors('status');

            $this->assertSame($from, $order->fresh()->status);
            $this->assertSame(0, AuditLog::where('action', 'order.status_updated')->count());
            Mail::assertNothingSent();
        }
    }

    public function test_wrong_password_prevents_order_status_transition(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $order = $this->order('pending');

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'processing',
                'password' => 'WrongPassword123!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, AuditLog::where('action', 'order.status_updated')->count());
        Mail::assertNothingSent();
    }

    public function test_order_status_emails_are_sent_only_after_valid_transitions(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $processingOrder = $this->order('pending');
        $completedOrder = $this->order('processing');
        $invalidOrder = $this->order('completed');

        $this->actingAs($admin)->patch(route('admin.orders.update', $processingOrder), [
            'status' => 'processing',
            'password' => 'Password123!',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.update', $completedOrder), [
            'status' => 'completed',
            'password' => 'Password123!',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.update', $invalidOrder), [
            'status' => 'processing',
            'password' => 'Password123!',
        ]);

        Mail::assertSent(
            OrderStatusNotification::class,
            fn (OrderStatusNotification $mail) => $mail->order->is($processingOrder)
                && $mail->notificationType === 'processing'
        );
        Mail::assertSent(
            OrderStatusNotification::class,
            fn (OrderStatusNotification $mail) => $mail->order->is($completedOrder)
                && $mail->notificationType === 'completed'
        );
        Mail::assertNotSent(
            OrderStatusNotification::class,
            fn (OrderStatusNotification $mail) => $mail->order->is($invalidOrder)
        );
    }

    public function test_audit_log_never_persists_password_values(): void
    {
        $admin = $this->admin();
        $order = $this->order('pending');

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'processing',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $auditLog = AuditLog::where('action', 'order.status_updated')->firstOrFail();
        $encoded = json_encode([$auditLog->old_values, $auditLog->new_values]);

        $this->assertStringNotContainsString('Password123!', $encoded);
        $this->assertStringNotContainsString('password', strtolower($encoded));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'password' => 'Password123!',
        ]);
    }

    private function assertAdminNoCacheHeaders($response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    private function catalog(): array
    {
        $category = Category::create(['name' => 'Skin Care', 'slug' => 'skin-care']);
        $brand = Brand::create(['name' => 'Lumina', 'slug' => 'lumina']);

        return [$category, $brand];
    }

    private function product(): array
    {
        [$category, $brand] = $this->catalog();

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Lumina Serum',
            'slug' => 'lumina-serum',
            'description' => 'A polished serum.',
            'product_type' => 'Serum',
            'properties' => ['Hydrating'],
            'gender' => 'Unisex',
            'size' => '30ml',
            'price' => 32,
            'stock' => 5,
            'is_active' => true,
        ]);

        return [$category, $brand, $product];
    }

    private function productPayload(Category $category, Brand $brand, array $overrides = []): array
    {
        return $overrides + [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Protected Serum',
            'description' => 'A protected product mutation.',
            'product_type' => 'Serum',
            'properties' => ['Hydrating'],
            'gender' => 'Unisex',
            'size' => '30ml',
            'price' => 32,
            'sale_price' => null,
            'stock' => 5,
            'is_active' => '1',
        ];
    }

    private function order(string $status): Order
    {
        return Order::create([
            'order_number' => 'LB-TEST-'.strtoupper($status).'-'.uniqid(),
            'customer_name' => 'Mira Customer',
            'customer_email' => 'mira@example.com',
            'customer_phone' => '+38344111999',
            'shipping_address' => 'Beauty Street 2',
            'shipping_city' => 'Prishtina',
            'shipping_country' => 'Kosovo',
            'status' => $status,
            'subtotal' => 64,
            'discount_total' => 0,
            'total' => 64,
        ]);
    }
}
