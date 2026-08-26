<?php

namespace App\Support\Catalog;

use App\Models\Product;
use InvalidArgumentException;

class ProductKnowledge
{
    public const SKIN_TYPES = [
        'dry' => 'Dry',
        'oily' => 'Oily',
        'combination' => 'Combination',
        'normal' => 'Normal',
        'sensitive' => 'Sensitive',
        'acne_prone' => 'Acne-prone',
    ];

    public const HAIR_TYPES = [
        'dry' => 'Dry',
        'oily' => 'Oily',
        'fine' => 'Fine',
        'thick' => 'Thick',
        'curly' => 'Curly',
        'color_treated' => 'Color-treated',
        'damaged' => 'Damaged',
    ];

    public const CONCERNS = [
        'acne' => 'Acne',
        'dryness' => 'Dryness',
        'dehydration' => 'Dehydration',
        'redness' => 'Redness',
        'pigmentation' => 'Pigmentation',
        'dark_spots' => 'Dark spots',
        'fine_lines' => 'Fine lines',
        'wrinkles' => 'Wrinkles',
        'sensitivity' => 'Sensitivity',
        'oil_control' => 'Oil control',
        'dullness' => 'Dullness',
        'damage' => 'Damage',
    ];

    public const BENEFITS = [
        'hydrating' => 'Hydrating',
        'brightening' => 'Brightening',
        'calming' => 'Calming',
        'anti_aging' => 'Anti-aging',
        'exfoliating' => 'Exfoliating',
        'moisturizing' => 'Moisturizing',
        'cleansing' => 'Cleansing',
        'repairing' => 'Repairing',
        'oil_control' => 'Oil control',
        'long_lasting' => 'Long lasting',
        'lightweight' => 'Lightweight',
    ];

    public const TARGET_AREAS = [
        'face' => 'Face',
        'eyes' => 'Eyes',
        'lips' => 'Lips',
        'body' => 'Body',
        'hair' => 'Hair',
        'scalp' => 'Scalp',
    ];

    public const INGREDIENTS = [
        'niacinamide' => 'Niacinamide',
        'hyaluronic_acid' => 'Hyaluronic acid',
        'retinol' => 'Retinol',
        'vitamin_c' => 'Vitamin C',
        'salicylic_acid' => 'Salicylic acid',
        'ceramides' => 'Ceramides',
        'peptides' => 'Peptides',
        'zinc' => 'Zinc',
    ];

    public const ROUTINE_STEPS = [
        'cleanser' => 'Cleanser',
        'toner' => 'Toner',
        'serum' => 'Serum',
        'treatment' => 'Treatment',
        'moisturizer' => 'Moisturizer',
        'sunscreen' => 'Sunscreen',
        'hair_treatment' => 'Hair treatment',
        'body_care' => 'Body care',
        'makeup' => 'Makeup',
        'fragrance' => 'Fragrance',
    ];

    public const SKINCARE_ROUTINE_ORDER = [
        'cleanser',
        'toner',
        'serum',
        'treatment',
        'moisturizer',
        'sunscreen',
    ];

    public const USAGE_FREQUENCIES = [
        'daily' => 'Daily',
        'twice_daily' => 'Twice daily',
        'weekly' => 'Weekly',
        'as_needed' => 'As needed',
    ];

    public const TRI_STATE_ATTRIBUTES = [
        'fragrance_free' => 'Fragrance-free',
        'cruelty_free' => 'Cruelty-free',
        'vegan' => 'Vegan',
        'alcohol_free' => 'Alcohol-free',
        'non_comedogenic' => 'Non-comedogenic',
    ];

    public static function options(): array
    {
        return [
            'product_type' => Product::PRODUCT_TYPES,
            'properties' => Product::PROPERTIES,
            'gender' => Product::GENDERS,
            'size' => Product::SIZES,
            'skin_types' => self::SKIN_TYPES,
            'hair_types' => self::HAIR_TYPES,
            'concerns' => self::CONCERNS,
            'benefits' => self::BENEFITS,
            'target_areas' => self::TARGET_AREAS,
            'key_ingredients' => self::INGREDIENTS,
            'routine_steps' => self::ROUTINE_STEPS,
            'usage_frequencies' => self::USAGE_FREQUENCIES,
            'tri_state_attributes' => self::TRI_STATE_ATTRIBUTES,
        ];
    }

    public static function arrayFields(): array
    {
        return ['skin_types', 'hair_types', 'concerns', 'benefits', 'target_areas', 'key_ingredients'];
    }

    public static function factualBooleanFields(): array
    {
        return ['am_suitable', 'pm_suitable', ...array_keys(self::TRI_STATE_ATTRIBUTES)];
    }

    public static function allowedValuesFor(string $field): array
    {
        return match ($field) {
            'skin_types' => array_keys(self::SKIN_TYPES),
            'hair_types' => array_keys(self::HAIR_TYPES),
            'concerns' => array_keys(self::CONCERNS),
            'benefits' => array_keys(self::BENEFITS),
            'target_areas' => array_keys(self::TARGET_AREAS),
            'key_ingredients' => array_keys(self::INGREDIENTS),
            'routine_step' => array_keys(self::ROUTINE_STEPS),
            'usage_frequency' => array_keys(self::USAGE_FREQUENCIES),
            'product_type' => Product::PRODUCT_TYPES,
            'gender' => Product::GENDERS,
            'size' => Product::SIZES,
            default => [],
        };
    }

    public static function normalizeArray(?array $values, string $field): ?array
    {
        if ($values === null) {
            return null;
        }

        $allowed = self::allowedValuesFor($field);
        $invalid = collect($values)
            ->reject(fn ($value): bool => is_string($value) && in_array($value, $allowed, true))
            ->values()
            ->all();

        if ($invalid !== []) {
            throw new InvalidArgumentException("Unsupported {$field} value.");
        }

        return collect($values)
            ->unique()
            ->values()
            ->all();
    }

    public static function routineStepRank(?string $routineStep): int
    {
        if ($routineStep === null) {
            return 999;
        }

        $rank = array_search($routineStep, self::SKINCARE_ROUTINE_ORDER, true);

        return $rank === false ? 900 : $rank;
    }
}
