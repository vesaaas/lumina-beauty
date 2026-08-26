<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductComparisonService
{
    public function compare(iterable $products): array
    {
        $items = collect($products)
            ->filter(fn (Product $product): bool => $product->is_active)
            ->map(fn (Product $product): array => $this->comparisonItem($product))
            ->values();

        return [
            'products' => $items->all(),
            'differences' => $this->differences($items),
        ];
    }

    private function comparisonItem(Product $product): array
    {
        $knowledge = $product->loadMissing(['brand', 'category'])->toKnowledgeArray();

        return [
            'id' => $knowledge['id'],
            'slug' => $knowledge['slug'],
            'name' => $knowledge['name'],
            'brand' => $knowledge['brand'],
            'category' => $knowledge['category'],
            'product_type' => $knowledge['product_type'],
            'price' => $knowledge['price'],
            'skin_types' => $knowledge['skin_types'],
            'concerns' => $knowledge['concerns'],
            'benefits' => $knowledge['benefits'],
            'key_ingredients' => $knowledge['key_ingredients'],
            'routine_step' => $knowledge['routine_step'],
            'attributes' => $knowledge['attributes'],
            'availability' => $knowledge['availability'],
        ];
    }

    private function differences(Collection $items): array
    {
        $fields = [
            'brand',
            'category',
            'product_type',
            'price',
            'skin_types',
            'concerns',
            'benefits',
            'key_ingredients',
            'routine_step',
            'attributes',
            'availability',
        ];

        return collect($fields)
            ->filter(function (string $field) use ($items): bool {
                return $items
                    ->pluck($field)
                    ->map(fn ($value): string => json_encode($value))
                    ->unique()
                    ->count() > 1;
            })
            ->values()
            ->all();
    }
}
