<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Support\Catalog\ProductKnowledge;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ProductRecommendationService
{
    public function __construct(private readonly ProductKnowledgeService $knowledgeService)
    {
    }

    public function recommend(array $criteria, int $limit = 6): array
    {
        $filters = [
            'available' => true,
            'max_price' => $criteria['max_price'] ?? null,
            'category' => $criteria['category'] ?? null,
            'product_type' => $criteria['product_type'] ?? null,
        ];

        $products = $this->knowledgeService
            ->query(array_filter($filters, fn ($value) => $value !== null && $value !== ''))
            ->where(function (Builder $query) use ($criteria): void {
                $this->applyRequiredKnowledgeCriteria($query, $criteria);
            })
            ->get()
            ->map(fn (Product $product): array => $this->score($product, $criteria))
            ->filter(fn (array $result): bool => $result['score'] > 0 && $this->matchesRequiredCriteria($result['matched'], $criteria))
            ->sortBy([
                ['score', 'desc'],
                ['price', 'asc'],
                ['id', 'asc'],
            ])
            ->take($limit)
            ->values();

        return $products->all();
    }

    private function applyRequiredKnowledgeCriteria(Builder $query, array $criteria): void
    {
        foreach ($this->criteriaMap() as $criterion => $rule) {
            $value = $criteria[$criterion] ?? null;

            if (is_string($value) && $value !== '') {
                if (! in_array($value, ProductKnowledge::allowedValuesFor($rule['field']), true)) {
                    throw new InvalidArgumentException("Unsupported {$criterion} criterion.");
                }

                $query->whereJsonContains($rule['field'], $value);
            }
        }
    }

    private function matchesRequiredCriteria(array $matched, array $criteria): bool
    {
        foreach (array_keys($this->criteriaMap()) as $criterion) {
            $value = $criteria[$criterion] ?? null;

            if (is_string($value) && $value !== '' && ($matched[$criterion] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    private function score(Product $product, array $criteria): array
    {
        $score = 0;
        $matched = [];

        foreach ($this->criteriaMap() as $criterion => $rule) {
            $value = $criteria[$criterion] ?? null;

            if (is_string($value) && $product->hasKnowledgeValue($rule['field'], $value)) {
                $score += $rule['points'];
                $matched[$criterion] = $value;
            }
        }

        return [
            'product' => $product->toKnowledgeArray(),
            'score' => $score,
            'matched' => $matched,
            'price' => (float) $product->active_price,
            'id' => $product->id,
        ];
    }

    private function criteriaMap(): array
    {
        return [
            'skin_type' => ['field' => 'skin_types', 'points' => 4],
            'hair_type' => ['field' => 'hair_types', 'points' => 4],
            'benefit' => ['field' => 'benefits', 'points' => 3],
            'concern' => ['field' => 'concerns', 'points' => 3],
            'ingredient' => ['field' => 'key_ingredients', 'points' => 2],
        ];
    }
}
