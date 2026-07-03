<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const PRODUCT_TYPES = [
        'Cleanser',
        'Serum',
        'Moisturizer',
        'Toner',
        'Face Mask',
        'Shampoo',
        'Conditioner',
        'Hair Oil',
        'Hair Mask',
        'Foundation',
        'Lipstick',
        'Concealer',
        'Blush',
        'Mascara',
        'Eau de Parfum',
        'Eau de Toilette',
        'Body Mist',
    ];

    public const PROPERTIES = [
        'Hydrating',
        'Brightening',
        'Anti-Aging',
        'Oil Control',
        'Sensitive Skin',
        'Long Lasting',
    ];

    public const GENDERS = [
        'Women',
        'Men',
        'Unisex',
    ];

    public const SIZES = [
        '30ml',
        '50ml',
        '100ml',
        '200ml',
    ];

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'product_type',
        'properties',
        'gender',
        'size',
        'price',
        'sale_price',
        'stock',
        'is_featured',
        'is_new_arrival',
        'is_hot_trend',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_hot_trend' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product): void {
            if ($product->isForceDeleting()) {
                throw new LogicException('Products cannot be permanently deleted because they may be referenced by customer orders.');
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function activePrice(): Attribute
    {
        return Attribute::get(fn () => $this->sale_price ?? $this->price);
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function isAvailableForPurchase(): bool
    {
        return $this->is_active && ! $this->isOutOfStock();
    }

    public function imageUrls(): array
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return $images
            ->map(fn (ProductImage $image) => str_starts_with($image->path, 'http') ? $image->path : Storage::disk('public')->url($image->path))
            ->values()
            ->all();
    }

    public function primaryImage(): string
    {
        return $this->imageUrls()[0] ?? 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=85';
    }

    public function toStorefrontArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'brand' => $this->brand?->name,
            'category' => $this->category?->name,
            'product_type' => $this->product_type,
            'properties' => $this->properties ?? [],
            'gender' => $this->gender,
            'size' => $this->size,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price === null ? null : (float) $this->sale_price,
            'stock' => $this->stock,
            'is_out_of_stock' => $this->isOutOfStock(),
            'is_available_for_purchase' => $this->isAvailableForPurchase(),
            'images' => $this->imageUrls(),
            'description' => $this->description,
            'is_featured' => $this->is_featured,
            'is_new_arrival' => $this->is_new_arrival,
            'is_hot_trend' => $this->is_hot_trend,
        ];
    }
}
