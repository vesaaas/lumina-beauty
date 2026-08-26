<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Support\Catalog\ProductKnowledge;
use Illuminate\Support\Collection;

class SkincareRoutineService
{
    public function __construct(private readonly ProductKnowledgeService $knowledgeService)
    {
    }

    public function build(array $criteria = []): array
    {
        $products = $this->knowledgeService
            ->query([
                'category' => 'Skin Care',
                'skin_type' => $criteria['skin_type'] ?? null,
                'concern' => $criteria['concern'] ?? null,
                'benefit' => $criteria['benefit'] ?? null,
                'max_price' => $criteria['max_price'] ?? null,
                'available' => true,
            ])
            ->whereIn('routine_step', ProductKnowledge::SKINCARE_ROUTINE_ORDER)
            ->get();

        $steps = collect(ProductKnowledge::SKINCARE_ROUTINE_ORDER)
            ->map(function (string $step) use ($products): ?Product {
                return $products
                    ->where('routine_step', $step)
                    ->sortBy([
                        [fn (Product $product): float => (float) $product->active_price, 'asc'],
                        ['id', 'asc'],
                    ])
                    ->first();
            })
            ->filter()
            ->values();

        if (($criteria['max_total'] ?? null) !== null) {
            $steps = $this->fitBudget($steps, (float) $criteria['max_total']);
        }

        return [
            'steps' => $steps
                ->sortBy(fn (Product $product): int => ProductKnowledge::routineStepRank($product->routine_step))
                ->map(fn (Product $product): array => [
                    'routine_step' => $product->routine_step,
                    'product' => $product->toKnowledgeArray(),
                ])
                ->values()
                ->all(),
            'total_price' => round($steps->sum(fn (Product $product): float => (float) $product->active_price), 2),
        ];
    }

    private function fitBudget(Collection $steps, float $maxTotal): Collection
    {
        while ($steps->sum(fn (Product $product): float => (float) $product->active_price) > $maxTotal && $steps->isNotEmpty()) {
            $steps = $steps
                ->sortByDesc(fn (Product $product): int => ProductKnowledge::routineStepRank($product->routine_step))
                ->slice(1)
                ->values();
        }

        return $steps;
    }
}
