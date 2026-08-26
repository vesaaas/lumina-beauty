<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductComparisonService;
use App\Services\Catalog\ProductKnowledgeService;
use App\Services\Catalog\ProductRecommendationService;
use App\Services\Catalog\SkincareRoutineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductKnowledgeLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_service_filters_by_structured_fields_price_active_and_stock(): void
    {
        $match = $this->product([
            'name' => 'Hydrating Dry Skin Serum',
            'slug' => 'hydrating-dry-skin-serum',
            'skin_types' => ['dry'],
            'concerns' => ['dehydration'],
            'benefits' => ['hydrating'],
            'key_ingredients' => ['hyaluronic_acid'],
            'routine_step' => 'serum',
            'price' => 42,
            'stock' => 4,
        ]);
        $this->product(['name' => 'Inactive Serum', 'slug' => 'inactive-serum', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'is_active' => false]);
        $this->product(['name' => 'Out Serum', 'slug' => 'out-serum', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'stock' => 0]);
        $this->product(['name' => 'Expensive Serum', 'slug' => 'expensive-serum', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'price' => 80]);

        $products = app(ProductKnowledgeService::class)->search([
            'skin_type' => 'dry',
            'concern' => 'dehydration',
            'benefit' => 'hydrating',
            'ingredient' => 'hyaluronic_acid',
            'routine_step' => 'serum',
            'max_price' => 50,
            'available' => true,
        ]);

        $this->assertTrue($products->contains($match));
        $this->assertSame(['Hydrating Dry Skin Serum'], $products->pluck('name')->all());
    }

    public function test_knowledge_array_preserves_null_and_empty_array_metadata_semantics(): void
    {
        $unknown = $this->product([
            'name' => 'Unknown Metadata Serum',
            'slug' => 'unknown-metadata-serum',
            'skin_types' => null,
            'concerns' => null,
            'benefits' => null,
            'key_ingredients' => null,
            'works_well_with' => null,
            'avoid_combining_with' => null,
        ]);
        $knownEmpty = $this->product([
            'name' => 'Known Empty Metadata Serum',
            'slug' => 'known-empty-metadata-serum',
            'skin_types' => [],
            'concerns' => [],
            'benefits' => [],
            'key_ingredients' => [],
            'works_well_with' => [],
            'avoid_combining_with' => [],
        ]);

        $unknownKnowledge = $unknown->toKnowledgeArray();
        $knownEmptyKnowledge = $knownEmpty->toKnowledgeArray();

        $this->assertNull($unknownKnowledge['skin_types']);
        $this->assertNull($unknownKnowledge['benefits']);
        $this->assertNull($unknownKnowledge['compatibility']['works_well_with']);
        $this->assertSame([], $knownEmptyKnowledge['skin_types']);
        $this->assertSame([], $knownEmptyKnowledge['benefits']);
        $this->assertSame([], $knownEmptyKnowledge['compatibility']['works_well_with']);
    }

    public function test_null_metadata_does_not_match_positive_filters(): void
    {
        $this->product(['name' => 'Unknown Metadata', 'slug' => 'unknown-metadata', 'skin_types' => null, 'benefits' => null]);
        $this->product(['name' => 'Explicit Dry Hydration', 'slug' => 'explicit-dry-hydration', 'skin_types' => ['dry'], 'benefits' => ['hydrating']]);

        $products = app(ProductKnowledgeService::class)->search([
            'skin_type' => 'dry',
            'benefit' => 'hydrating',
        ]);

        $this->assertSame(['Explicit Dry Hydration'], $products->pluck('name')->all());
    }

    public function test_hair_metadata_and_nullable_attributes_are_filterable_without_treating_unknown_as_false(): void
    {
        $knownFalse = $this->product([
            'name' => 'Documented Hair Mask',
            'slug' => 'documented-hair-mask',
            'category_id' => $this->category('Hair Care')->id,
            'hair_types' => ['damaged'],
            'benefits' => ['repairing'],
            'fragrance_free' => false,
            'product_type' => 'Hair Mask',
        ]);
        $this->product(['name' => 'Unknown Hair Mask', 'slug' => 'unknown-hair-mask', 'category_id' => $knownFalse->category_id, 'hair_types' => null, 'fragrance_free' => null, 'product_type' => 'Hair Mask']);

        $service = app(ProductKnowledgeService::class);

        $this->assertSame(['Documented Hair Mask'], $service->search(['hair_type' => 'damaged'])->pluck('name')->all());
        $this->assertSame(['Documented Hair Mask'], $service->search(['attributes' => ['fragrance_free' => false]])->pluck('name')->all());
    }

    public function test_recommendations_are_deterministic_and_use_only_explicit_matches(): void
    {
        $best = $this->product([
            'name' => 'Dry Hydration Serum',
            'slug' => 'dry-hydration-serum',
            'skin_types' => ['dry'],
            'benefits' => ['hydrating'],
            'concerns' => ['dehydration'],
            'price' => 30,
        ]);
        $this->product(['name' => 'Dry Basic Cream', 'slug' => 'dry-basic-cream', 'skin_types' => ['dry'], 'price' => 20]);
        $this->product(['name' => 'Unknown Hydration', 'slug' => 'unknown-hydration', 'skin_types' => null, 'benefits' => null, 'price' => 10]);
        $this->product(['name' => 'Unavailable Match', 'slug' => 'unavailable-match', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'stock' => 0]);
        $this->product(['name' => 'Over Budget Match', 'slug' => 'over-budget-match', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'price' => 60]);

        $results = app(ProductRecommendationService::class)->recommend([
            'skin_type' => 'dry',
            'benefit' => 'hydrating',
            'concern' => 'dehydration',
            'max_price' => 50,
        ]);

        $this->assertSame($best->id, $results[0]['product']['id']);
        $this->assertSame(10, $results[0]['score']);
        $this->assertSame(['Dry Hydration Serum'], collect($results)->pluck('product.name')->all());
    }

    public function test_recommendations_require_full_categorical_match_for_multi_criterion_requests(): void
    {
        $this->product(['name' => 'Full Match', 'slug' => 'full-match', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'concerns' => ['dehydration']]);
        $this->product(['name' => 'Weak Partial Match', 'slug' => 'weak-partial-match', 'skin_types' => ['dry']]);

        $results = app(ProductRecommendationService::class)->recommend([
            'skin_type' => 'dry',
            'benefit' => 'hydrating',
            'concern' => 'dehydration',
        ]);

        $this->assertSame(['Full Match'], collect($results)->pluck('product.name')->all());
    }

    public function test_recommendations_use_sale_price_then_id_for_deterministic_tie_breaking(): void
    {
        $sale = $this->product(['name' => 'Sale Match', 'slug' => 'sale-match', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'price' => 50, 'sale_price' => 20]);
        $this->product(['name' => 'Regular Match', 'slug' => 'regular-match', 'skin_types' => ['dry'], 'benefits' => ['hydrating'], 'price' => 30]);

        $results = app(ProductRecommendationService::class)->recommend([
            'skin_type' => 'dry',
            'benefit' => 'hydrating',
        ]);

        $this->assertSame($sale->id, $results[0]['product']['id']);
        $this->assertSame(20.0, $results[0]['price']);

        $first = $this->product(['name' => 'First Equal Match', 'slug' => 'first-equal-match', 'skin_types' => ['sensitive'], 'benefits' => ['calming'], 'price' => 25]);
        $this->product(['name' => 'Second Equal Match', 'slug' => 'second-equal-match', 'skin_types' => ['sensitive'], 'benefits' => ['calming'], 'price' => 25]);

        $equalResults = app(ProductRecommendationService::class)->recommend([
            'skin_type' => 'sensitive',
            'benefit' => 'calming',
        ]);

        $this->assertSame($first->id, $equalResults[0]['product']['id']);
    }

    public function test_best_recommendation_can_be_found_beyond_fifty_newer_products(): void
    {
        $best = $this->product([
            'name' => 'Old Best Match',
            'slug' => 'old-best-match',
            'skin_types' => ['dry'],
            'benefits' => ['hydrating'],
            'concerns' => ['dehydration'],
            'price' => 15,
        ]);

        for ($i = 0; $i < 60; $i++) {
            $this->product([
                'name' => 'Newer Partial '.$i,
                'slug' => 'newer-partial-'.$i,
                'skin_types' => ['dry'],
                'price' => 10,
            ]);
        }

        $results = app(ProductRecommendationService::class)->recommend([
            'skin_type' => 'dry',
            'benefit' => 'hydrating',
            'concern' => 'dehydration',
        ]);

        $this->assertSame($best->id, $results[0]['product']['id']);
    }

    public function test_sensitive_skin_and_concern_recommendations_exclude_inactive_products(): void
    {
        $this->product(['name' => 'Sensitive Calming Cream', 'slug' => 'sensitive-calming-cream', 'skin_types' => ['sensitive'], 'concerns' => ['redness'], 'benefits' => ['calming']]);
        $this->product(['name' => 'Inactive Calming Cream', 'slug' => 'inactive-calming-cream', 'skin_types' => ['sensitive'], 'concerns' => ['redness'], 'benefits' => ['calming'], 'is_active' => false]);

        $results = app(ProductRecommendationService::class)->recommend([
            'skin_type' => 'sensitive',
            'concern' => 'redness',
        ]);

        $this->assertSame(['Sensitive Calming Cream'], collect($results)->pluck('product.name')->all());
    }

    public function test_comparison_returns_structured_safe_fields_and_differences(): void
    {
        $serum = $this->product(['name' => 'Serum A', 'slug' => 'serum-a', 'benefits' => ['hydrating'], 'key_ingredients' => ['hyaluronic_acid'], 'routine_step' => 'serum']);
        $cream = $this->product(['name' => 'Cream B', 'slug' => 'cream-b', 'benefits' => ['moisturizing'], 'routine_step' => 'moisturizer', 'product_type' => 'Moisturizer']);

        $comparison = app(ProductComparisonService::class)->compare([$serum, $cream]);

        $this->assertSame(['Serum A', 'Cream B'], collect($comparison['products'])->pluck('name')->all());
        $this->assertContains('product_type', $comparison['differences']);
        $this->assertArrayNotHasKey('created_at', $comparison['products'][0]);
    }

    public function test_skincare_routine_orders_steps_and_respects_budget_and_stock(): void
    {
        $this->product(['name' => 'Dry Cleanser', 'slug' => 'dry-cleanser', 'skin_types' => ['dry'], 'routine_step' => 'cleanser', 'price' => 20]);
        $this->product(['name' => 'Dry Serum', 'slug' => 'dry-serum', 'skin_types' => ['dry'], 'routine_step' => 'serum', 'price' => 25]);
        $this->product(['name' => 'Dry Cream', 'slug' => 'dry-cream', 'skin_types' => ['dry'], 'routine_step' => 'moisturizer', 'price' => 30]);
        $this->product(['name' => 'Out Sunscreen', 'slug' => 'out-sunscreen', 'skin_types' => ['dry'], 'routine_step' => 'sunscreen', 'price' => 10, 'stock' => 0]);

        $routine = app(SkincareRoutineService::class)->build(['skin_type' => 'dry', 'max_total' => 50]);

        $this->assertSame(['cleanser', 'serum'], collect($routine['steps'])->pluck('routine_step')->all());
        $this->assertSame(45.0, $routine['total_price']);
    }

    public function test_routine_can_find_eligible_product_beyond_fifty_newer_products(): void
    {
        $cleanser = $this->product([
            'name' => 'Old Dry Cleanser',
            'slug' => 'old-dry-cleanser',
            'skin_types' => ['dry'],
            'routine_step' => 'cleanser',
            'price' => 18,
        ]);

        for ($i = 0; $i < 60; $i++) {
            $this->product([
                'name' => 'Newer Dry Non Routine '.$i,
                'slug' => 'newer-dry-non-routine-'.$i,
                'skin_types' => ['dry'],
                'routine_step' => null,
            ]);
        }

        $routine = app(SkincareRoutineService::class)->build(['skin_type' => 'dry']);

        $this->assertSame($cleanser->id, $routine['steps'][0]['product']['id']);
    }

    public function test_non_skincare_products_cannot_enter_skincare_routine(): void
    {
        $makeupCategory = $this->category('Makeup');
        $this->product([
            'name' => 'Makeup Serum',
            'slug' => 'makeup-serum',
            'category_id' => $makeupCategory->id,
            'skin_types' => ['dry'],
            'routine_step' => 'serum',
            'price' => 5,
        ]);
        $skinCare = $this->product([
            'name' => 'Skin Care Serum',
            'slug' => 'skin-care-serum',
            'skin_types' => ['dry'],
            'routine_step' => 'serum',
            'price' => 20,
        ]);

        $routine = app(SkincareRoutineService::class)->build(['skin_type' => 'dry']);

        $this->assertSame([$skinCare->id], collect($routine['steps'])->pluck('product.id')->all());
    }

    public function test_admin_rejects_invalid_knowledge_values(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $brand = $this->brand();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, $brand, [
                'skin_types' => ['unsupported'],
                'password' => 'Password123!',
            ]))
            ->assertSessionHasErrors('skin_types.0');

        $this->assertDatabaseMissing('products', ['name' => 'Admin Knowledge Product']);
    }

    public function test_admin_persists_valid_knowledge_and_unknown_nullable_attributes(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $brand = $this->brand();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, $brand, [
                'skin_types' => ['dry'],
                'benefits' => ['hydrating'],
                'key_ingredients' => ['hyaluronic_acid'],
                'routine_step' => 'serum',
                'fragrance_free' => 'unknown',
                'vegan' => '1',
                'password' => 'Password123!',
            ]))
            ->assertSessionHas('admin_status', 'Product created.');

        $product = Product::where('name', 'Admin Knowledge Product')->firstOrFail();

        $this->assertSame(['dry'], $product->skin_types);
        $this->assertSame(['hydrating'], $product->benefits);
        $this->assertNull($product->fragrance_free);
        $this->assertTrue($product->vegan);
    }

    public function test_admin_can_clear_all_checkbox_values_to_known_empty_array(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $brand = $this->brand();
        $product = $this->product(['skin_types' => ['dry'], 'benefits' => ['hydrating']]);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->productPayload($category, $brand, [
                'name' => 'Cleared Knowledge Product',
                'skin_types_present' => '1',
                'benefits_present' => '1',
                'password' => 'Password123!',
            ]))
            ->assertSessionHas('admin_status', 'Product updated.');

        $product->refresh();

        $this->assertSame([], $product->skin_types);
        $this->assertSame([], $product->benefits);
    }

    public function test_admin_clear_all_checkbox_values_survive_validation_failure(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $brand = $this->brand();
        $product = $this->product(['skin_types' => ['dry']]);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $this->productPayload($category, $brand, [
                'skin_types_present' => '1',
                'sale_price' => 99,
                'password' => 'Password123!',
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('sale_price');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertDontSee('name="skin_types[]" value="dry" checked', false);
    }

    public function test_customer_safe_comparison_excludes_inactive_products(): void
    {
        $active = $this->product(['name' => 'Active Compare', 'slug' => 'active-compare']);
        $inactive = $this->product(['name' => 'Inactive Compare', 'slug' => 'inactive-compare', 'is_active' => false]);

        $comparison = app(ProductComparisonService::class)->compare([$active, $inactive]);

        $this->assertSame(['Active Compare'], collect($comparison['products'])->pluck('name')->all());
    }

    public function test_am_pm_filtering_respects_true_false_and_unknown(): void
    {
        $this->product(['name' => 'AM Product', 'slug' => 'am-product', 'am_suitable' => true, 'pm_suitable' => null]);
        $this->product(['name' => 'PM No Product', 'slug' => 'pm-no-product', 'am_suitable' => false, 'pm_suitable' => false]);
        $this->product(['name' => 'Unknown Time Product', 'slug' => 'unknown-time-product', 'am_suitable' => null, 'pm_suitable' => null]);

        $service = app(ProductKnowledgeService::class);

        $this->assertSame(['AM Product'], $service->search(['attributes' => ['am_suitable' => true]])->pluck('name')->all());
        $this->assertSame(['PM No Product'], $service->search(['attributes' => ['pm_suitable' => false]])->pluck('name')->all());
    }

    public function test_invalid_internal_knowledge_values_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->product(['skin_types' => ['not-supported']]);
    }

    public function test_existing_storefront_filters_continue_to_work(): void
    {
        $this->product(['name' => 'Visible Perfume', 'slug' => 'visible-perfume', 'product_type' => 'Eau de Parfum', 'gender' => 'Unisex', 'size' => '100ml', 'category_id' => $this->category('Perfume')->id]);
        $this->product(['name' => 'Other Perfume', 'slug' => 'other-perfume', 'product_type' => 'Eau de Parfum', 'gender' => 'Women', 'size' => '100ml', 'category_id' => $this->category('Perfume')->id]);

        $response = $this->get(route('products.index', [
            'category' => 'Perfume',
            'gender' => 'Unisex',
            'size' => '100ml',
        ]));

        $response->assertOk()->assertSee('Visible Perfume');
        $this->assertSame(['Visible Perfume'], collect($response->viewData('filteredProducts'))->pluck('name')->all());
    }

    private function product(array $overrides = []): Product
    {
        $category = isset($overrides['category_id']) ? null : $this->category();
        $brand = isset($overrides['brand_id']) ? null : $this->brand();

        return Product::create($overrides + [
            'category_id' => $category?->id ?? $overrides['category_id'],
            'brand_id' => $brand?->id ?? $overrides['brand_id'] ?? $this->brand()->id,
            'name' => 'Knowledge Product '.uniqid(),
            'slug' => 'knowledge-product-'.uniqid(),
            'description' => 'Demo catalog metadata for deterministic product knowledge tests.',
            'product_type' => 'Serum',
            'properties' => ['Hydrating'],
            'gender' => 'Unisex',
            'size' => '30ml',
            'price' => 25,
            'sale_price' => null,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    private function category(string $name = 'Skin Care'): Category
    {
        return Category::firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name]);
    }

    private function brand(string $name = 'Lumina'): Brand
    {
        return Brand::firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'password' => 'Password123!',
        ]);
    }

    private function productPayload(Category $category, Brand $brand, array $overrides = []): array
    {
        return $overrides + [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Admin Knowledge Product',
            'description' => 'A protected product knowledge mutation.',
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
}
