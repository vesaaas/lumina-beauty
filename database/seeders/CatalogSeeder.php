<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = ['Skin Care', 'Hair Care', 'Makeup', 'Perfume', 'Body'];

        foreach ($categoryNames as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => 'Curated '.$name.' essentials for Lumina Beauty customers.'],
            );
        }

        foreach (config('lumina.products') as $index => $item) {
            $brand = Brand::updateOrCreate(
                ['slug' => Str::slug($item['brand'])],
                ['name' => $item['brand'], 'description' => 'Premium beauty products from '.$item['brand'].'.'],
            );

            $category = Category::firstWhere('slug', Str::slug($item['category']));

            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'product_type' => $item['product_type'],
                    'properties' => $item['properties'],
                    'gender' => $item['gender'],
                    'size' => $item['size'],
                    'skin_types' => $item['skin_types'] ?? null,
                    'hair_types' => $item['hair_types'] ?? null,
                    'concerns' => $item['concerns'] ?? null,
                    'benefits' => $item['benefits'] ?? null,
                    'target_areas' => $item['target_areas'] ?? null,
                    'key_ingredients' => $item['key_ingredients'] ?? null,
                    'routine_step' => $item['routine_step'] ?? null,
                    'usage_instructions' => $item['usage_instructions'] ?? null,
                    'usage_frequency' => $item['usage_frequency'] ?? null,
                    'am_suitable' => $item['am_suitable'] ?? null,
                    'pm_suitable' => $item['pm_suitable'] ?? null,
                    'fragrance_free' => $item['fragrance_free'] ?? null,
                    'cruelty_free' => $item['cruelty_free'] ?? null,
                    'vegan' => $item['vegan'] ?? null,
                    'alcohol_free' => $item['alcohol_free'] ?? null,
                    'non_comedogenic' => $item['non_comedogenic'] ?? null,
                    'works_well_with' => $item['works_well_with'] ?? null,
                    'avoid_combining_with' => $item['avoid_combining_with'] ?? null,
                    'knowledge_warnings' => $item['knowledge_warnings'] ?? null,
                    'price' => $item['price'],
                    'sale_price' => $item['sale_price'],
                    'stock' => 20 + ($index * 3),
                    'is_featured' => $index < 4,
                    'is_new_arrival' => $index < 8,
                    'is_hot_trend' => in_array($index, [0, 2, 3, 7], true),
                    'is_active' => true,
                ],
            );

            foreach ($item['images'] as $sortOrder => $path) {
                $product->images()->updateOrCreate(
                    ['sort_order' => $sortOrder],
                    ['path' => $path, 'alt_text' => $item['name']],
                );
            }
        }
    }
}
