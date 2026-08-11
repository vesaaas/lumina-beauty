<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontViewData
{
    /**
     * Data required by the shared storefront layout.
     *
     * @return array<string, mixed>
     */
    public function layoutData(Request $request, ?array $products = null): array
    {
        return [
            'products' => $products ?? $this->storefrontProducts(),
            'categories' => Category::orderBy('name')->pluck('name')->all(),
            'brands' => Brand::orderBy('name')->pluck('name')->all(),
            'cartCount' => $this->cartCount($request),
            'favoritesCount' => $this->favoritesCount($request),
        ];
    }

    /**
     * Common data used by storefront pages in addition to layout data.
     *
     * @return array<string, mixed>
     */
    public function storefrontData(Request $request): array
    {
        $products = Product::with(['brand', 'category', 'images'])
            ->where('is_active', true)
            ->latest()
            ->get();
        $storefrontProducts = $products->map->toStorefrontArray()->all();

        return array_merge($this->layoutData($request, $storefrontProducts), [
            'products' => $storefrontProducts,
            'newArrivals' => $products->where('is_new_arrival', true)->take(8)->map->toStorefrontArray()->all(),
            'saleProducts' => $products->whereNotNull('sale_price')->values()->map->toStorefrontArray()->all(),
            'categoryModels' => Category::withCount('products')->orderBy('name')->get(),
            'brandModels' => Brand::with(['products.images'])->withCount('products')->orderBy('name')->get(),
            'heroSlides' => [
                'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=1500&q=88',
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function storefrontProducts(): array
    {
        return Product::with(['brand', 'category', 'images'])
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map->toStorefrontArray()
            ->all();
    }

    private function cartCount(Request $request): int
    {
        if (! $request->user()) {
            return array_sum($this->guestCart($request));
        }

        return (int) CartItem::query()
            ->where($this->ownerAttributes($request))
            ->sum('quantity');
    }

    private function favoritesCount(Request $request): int
    {
        return Favorite::query()
            ->where($this->ownerAttributes($request))
            ->count();
    }

    /**
     * @return array<string, int|string>
     */
    private function ownerAttributes(Request $request): array
    {
        return $request->user()
            ? ['user_id' => $request->user()->id]
            : ['session_id' => $request->hasSession() ? $request->session()->getId() : ''];
    }

    /**
     * @return array<int, int>
     */
    private function guestCart(Request $request): array
    {
        return collect($request->hasSession() ? $request->session()->get('guest_cart', []) : [])
            ->mapWithKeys(fn ($quantity, $productId) => [(int) $productId => max(1, min(99, (int) $quantity))])
            ->all();
    }
}
