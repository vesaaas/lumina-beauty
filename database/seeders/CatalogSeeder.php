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
