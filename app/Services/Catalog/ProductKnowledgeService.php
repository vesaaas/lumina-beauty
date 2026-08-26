<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Support\Catalog\ProductKnowledge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProductKnowledgeService
{
    public function query(array $filters = [], bool $includeInactive = false): Builder
    {
        $query = Product::query()->with(['brand', 'category', 'images']);

        if (! $includeInactive) {
            $query->active();
        }

        if (($filters['available'] ?? false) === true) {
            $query->where('stock', '>', 0);
        }

        $this->applyRelationshipFilter($query, $filters, 'category');
        $this->applyRelationshipFilter($query, $filters, 'brand');
        $this->applyScalarFilter($query, $filters, 'product_type');
        $this->applyScalarFilter($query, $filters, 'routine_step');
        $this->applyKnowledgeFilter($query, $filters, 'skin_type', 'skin_types');
        $this->applyKnowledgeFilter($query, $filters, 'hair_type', 'hair_types');
        $this->applyKnowledgeFilter($query, $filters, 'concern', 'concerns');
        $this->applyKnowledgeFilter($query, $filters, 'benefit', 'benefits');
        $this->applyKnowledgeFilter($query, $filters, 'ingredient', 'key_ingredients');
        $this->applyAttributeFilters($query, $filters['attributes'] ?? []);
        $this->applyPriceFilters($query, $filters);
        $this->applyKeywordSearch($query, trim((string) ($filters['keyword'] ?? '')));

        return $query;
    }

    public function search(array $filters = [], int $limit = 12, bool $includeInactive = false): Collection
    {
        $query = $this->query($filters, $includeInactive)->latest();

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function knowledge(array $filters = [], int $limit = 12): array
    {
        return $this->search($filters, $limit)
            ->map->toKnowledgeArray()
            ->values()
            ->all();
    }

    private function applyRelationshipFilter(Builder $query, array $filters, string $relationship): void
    {
        $value = trim((string) ($filters[$relationship] ?? ''));

        if ($value === '') {
            return;
        }

        $query->whereHas($relationship, function (Builder $query) use ($value): void {
            $query->where('slug', $value)->orWhere('name', $value);
        });
    }

    private function applyScalarFilter(Builder $query, array $filters, string $field): void
    {
        $value = $filters[$field] ?? null;

        if ($value === null || $value === '') {
            return;
        }

        $allowed = ProductKnowledge::allowedValuesFor($field);

        if ($allowed !== [] && ! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported {$field} filter.");
        }

        $query->where($field, $value);
    }

    private function applyKnowledgeFilter(Builder $query, array $filters, string $filter, string $field): void
    {
        $value = $filters[$filter] ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (! in_array($value, ProductKnowledge::allowedValuesFor($field), true)) {
            throw new InvalidArgumentException("Unsupported {$filter} filter.");
        }

        $query->withKnowledgeValue($field, $value);
    }

    private function applyAttributeFilters(Builder $query, mixed $attributes): void
    {
        if (! is_array($attributes)) {
            return;
        }

        foreach ($attributes as $attribute => $expected) {
            if (! in_array($attribute, ProductKnowledge::factualBooleanFields(), true)) {
                throw new InvalidArgumentException("Unsupported {$attribute} attribute filter.");
            }

            if (! is_bool($expected)) {
                throw new InvalidArgumentException("Attribute filters must be boolean.");
            }

            $query->where($attribute, $expected);
        }
    }

    private function applyPriceFilters(Builder $query, array $filters): void
    {
        if (($filters['min_price'] ?? null) !== null) {
            $min = (float) $filters['min_price'];

            $query->where(function (Builder $query) use ($min): void {
                $query
                    ->where(fn (Builder $query) => $query->whereNotNull('sale_price')->where('sale_price', '>=', $min))
                    ->orWhere(fn (Builder $query) => $query->whereNull('sale_price')->where('price', '>=', $min));
            });
        }

        if (($filters['max_price'] ?? null) !== null) {
            $max = (float) $filters['max_price'];

            $query->where(function (Builder $query) use ($max): void {
                $query
                    ->where(fn (Builder $query) => $query->whereNotNull('sale_price')->where('sale_price', '<=', $max))
                    ->orWhere(fn (Builder $query) => $query->whereNull('sale_price')->where('price', '<=', $max));
            });
        }
    }

    private function applyKeywordSearch(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $query->where(function (Builder $query) use ($keyword): void {
            $query
                ->where('name', 'like', '%'.$keyword.'%')
                ->orWhere('description', 'like', '%'.$keyword.'%')
                ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', '%'.$keyword.'%'))
                ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', '%'.$keyword.'%'));
        });
    }
}
