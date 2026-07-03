<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\Product;
use App\Mail\StorefrontPageMessage;
use App\Mail\OrderStatusNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    public function home()
    {
        return view('home', $this->viewData());
    }

    public function products(Request $request)
    {
        $search = trim((string) $request->query('search'));

        $query = Product::query()
            ->with(['brand', 'category', 'images'])
            ->where('is_active', true)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', '%'.$search.'%'));
                });
            });

        $this->applyProductFilters($query, $request, true);
        $this->applyProductSorting($query, (string) $request->query('sort', 'default'));

        $products = $query
            ->get()
            ->map->toStorefrontArray()
            ->all();

        return view('products.index', $this->viewData([
            'categoryFilters' => $this->categoryFilterOptions(),
            'activeFilters' => $request->only(['category', 'product_type', 'property', 'gender', 'size', 'price_min', 'price_max', 'sort', 'search']),
            'filteredProducts' => $products,
        ]));
    }

    public function product(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('products.show', $this->viewData([
            'product' => $product->load(['brand', 'category', 'images'])->toStorefrontArray(),
        ]));
    }

    public function categories()
    {
        return view('categories.index', $this->viewData());
    }

    public function category(Request $request, Category $category)
    {
        $query = $category->products()
            ->with(['brand', 'category', 'images'])
            ->where('is_active', true);

        $this->applyProductFilters($query, $request);
        $this->applyProductSorting($query, (string) $request->query('sort', 'default'));

        return view('categories.show', $this->viewData([
            'category' => $category->name,
            'categoryFilters' => $this->categoryFilterOptions(),
            'activeFilters' => $request->only(['product_type', 'property', 'gender', 'size', 'price_min', 'price_max', 'sort']),
            'filteredProducts' => $query
                ->get()
                ->map->toStorefrontArray()
                ->all(),
        ]));
    }

    public function brands()
    {
        return view('brands.index', $this->viewData());
    }

    public function brand(Brand $brand)
    {
        return view('brands.show', $this->viewData([
            'brand' => $brand->name,
            'filteredProducts' => $brand->products()
                ->with(['brand', 'category', 'images'])
                ->where('is_active', true)
                ->latest()
                ->get()
                ->map->toStorefrontArray()
                ->all(),
        ]));
    }

    public function favorites(Request $request)
    {
        $favorites = $this->favoritesQuery($request)
            ->with(['product.brand', 'product.category', 'product.images'])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter()
            ->map->toStorefrontArray()
            ->values()
            ->all();

        return view('favorites.index', $this->viewData(['favoriteProducts' => $favorites]));
    }

    public function toggleFavorite(Request $request, Product $product): RedirectResponse
    {
        $owner = $this->ownerAttributes($request);
        $favorite = Favorite::where($owner)->where('product_id', $product->id)->first();

        if ($favorite) {
            $favorite->delete();
            return back()->with('status', 'Product removed from favorites.');
        }

        Favorite::create($owner + ['product_id' => $product->id]);

        return back()->with('status', 'Product added to favorites.');
    }

    public function removeFavorite(Request $request, Product $product): RedirectResponse
    {
        Favorite::where($this->ownerAttributes($request))->where('product_id', $product->id)->delete();

        return back()->with('status', 'Product removed from favorites.');
    }

    public function cart(Request $request)
    {
        return view('cart.index', $this->viewData([
            'cartItems' => $this->cartItems($request),
            'cartTotal' => $this->cartTotal($request),
        ]));
    }

    public function addToCart(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));

        if (! $request->user()) {
            $this->putGuestCartQuantity($request, $product, $this->guestCartQuantity($request, $product) + $quantity);

            return back()->with('status', 'Product added to cart.');
        }

        $owner = $this->ownerAttributes($request);
        $cartItem = CartItem::firstOrNew($owner + ['product_id' => $product->id]);
        $cartItem->quantity = min(99, ($cartItem->exists ? $cartItem->quantity : 0) + $quantity);
        $cartItem->save();

        return back()->with('status', 'Product added to cart.');
    }

    public function updateCart(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($this->ownsCartItem($request, $cartItem), 403);

        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));
        $cartItem->update(['quantity' => $quantity]);

        return back()->with('status', 'Cart updated.');
    }

    public function updateGuestCart(Request $request, Product $product): RedirectResponse
    {
        abort_if($request->user(), 404);
        abort_unless($product->is_active, 404);

        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));
        $this->putGuestCartQuantity($request, $product, $quantity);

        return back()->with('status', 'Cart updated.');
    }

    public function removeCart(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($this->ownsCartItem($request, $cartItem), 403);

        $cartItem->delete();

        return back()->with('status', 'Product removed from cart.');
    }

    public function removeGuestCart(Request $request, Product $product): RedirectResponse
    {
        abort_if($request->user(), 404);

        $cart = $this->guestCart($request);
        unset($cart[$product->id]);
        $request->session()->put('guest_cart', $cart);

        return back()->with('status', 'Product removed from cart.');
    }

    public function checkout(Request $request)
    {
        return view('checkout.index', $this->viewData([
            'cartItems' => $this->cartItems($request),
            'cartTotal' => $this->cartTotal($request),
        ]));
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $cartItems = $this->cartItems($request);
        abort_if($cartItems->isEmpty(), 422, 'Your cart is empty.');

        $attributes = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_country' => ['required', 'string', 'max:120'],
        ]);

        $order = DB::transaction(function () use ($request, $cartItems, $attributes): Order {
            $subtotal = $cartItems->sum(fn ($item) => (float) $item->product->price * $item->quantity);
            $total = $cartItems->sum(fn ($item) => (float) $item->product->active_price * $item->quantity);

            $order = Order::create($attributes + [
                'user_id' => $request->user()?->id,
                'order_number' => $this->makeOrderNumber(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_total' => max(0, $subtotal - $total),
                'total' => $total,
            ]);

            foreach ($cartItems as $item) {
                $product = $item->product;
                $unitPrice = (float) $product->active_price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'brand_name' => $product->brand?->name,
                    'category_name' => $product->category?->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => $unitPrice * $item->quantity,
                ]);

                $product->decrement('stock', min($product->stock, $item->quantity));
            }

            $request->user()
                ? $this->cartQuery($request)->delete()
                : $request->session()->forget('guest_cart');

            return $order;
        });

        Mail::to($order->customer_email)->send(new OrderStatusNotification($order, 'pending'));

        return redirect()->route('orders.thank-you', $order)->with('status', 'Order placed successfully.');
    }

    public function thankYou(Order $order)
    {
        return view('orders.thank-you', $this->viewData(['order' => $order->load('items')]));
    }

    public function about()
    {
        return view('about', $this->viewData());
    }

    public function contact()
    {
        return view('contact', $this->viewData());
    }

    public function sendAboutMessage(Request $request): RedirectResponse
    {
        $attributes = $request->validateWithBag('about', [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        Mail::to($this->storefrontInbox())->send(new StorefrontPageMessage(
            page: 'About Us',
            messageSubject: 'New Lumina Beauty about page message',
            attributes: $attributes,
        ));

        return back()->with('about_status', 'Thank you. Your About Us message was sent.');
    }

    public function sendContactMessage(Request $request): RedirectResponse
    {
        $attributes = $request->validateWithBag('contact', [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'topic' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        Mail::to($this->storefrontInbox())->send(new StorefrontPageMessage(
            page: 'Contact Us',
            messageSubject: 'New Lumina Beauty contact message',
            attributes: $attributes,
        ));

        return back()->with('contact_status', 'Thank you. Your message was sent.');
    }

    public function hotTrends()
    {
        return view('placeholders.hot-trends', $this->viewData([
            'hotTrendProducts' => Product::with(['brand', 'category', 'images'])
                ->where('is_active', true)
                ->where('is_hot_trend', true)
                ->latest()
                ->get()
                ->map->toStorefrontArray()
                ->all(),
        ]));
    }

    public function sales()
    {
        return view('placeholders.sales', $this->viewData([
            'filteredProducts' => Product::with(['brand', 'category', 'images'])
                ->where('is_active', true)
                ->whereNotNull('sale_price')
                ->latest()
                ->get()
                ->map->toStorefrontArray()
                ->all(),
        ]));
    }

    private function viewData(array $extra = []): array
    {
        $products = Product::with(['brand', 'category', 'images'])
            ->where('is_active', true)
            ->latest()
            ->get();
        $storefrontProducts = $products->map->toStorefrontArray()->all();

        return array_merge([
            'products' => $storefrontProducts,
            'newArrivals' => $products->where('is_new_arrival', true)->take(8)->map->toStorefrontArray()->all(),
            'saleProducts' => $products->whereNotNull('sale_price')->values()->map->toStorefrontArray()->all(),
            'categories' => Category::orderBy('name')->pluck('name')->all(),
            'categoryModels' => Category::withCount('products')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->pluck('name')->all(),
            'brandModels' => Brand::with(['products.images'])->withCount('products')->orderBy('name')->get(),
            'cartCount' => $this->cartCount(request()),
            'favoritesCount' => $this->favoritesQuery(request())->count(),
            'heroSlides' => [
                'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=1500&q=88',
                'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=1500&q=88',
            ],
        ], $extra);
    }

    private function storefrontInbox(): string
    {
        return env('ADMIN_EMAIL', config('mail.from.address'));
    }

    private function ownerAttributes(Request $request): array
    {
        return $request->user()
            ? ['user_id' => $request->user()->id]
            : ['session_id' => $request->session()->getId()];
    }

    private function cartQuery(Request $request): Builder
    {
        return CartItem::query()->where($this->ownerAttributes($request));
    }

    private function favoritesQuery(Request $request): Builder
    {
        return Favorite::query()->where($this->ownerAttributes($request));
    }

    private function cartItems(Request $request): Collection
    {
        if (! $request->user()) {
            $cart = $this->guestCart($request);

            if ($cart === []) {
                return collect();
            }

            return Product::with(['brand', 'category', 'images'])
                ->whereIn('id', array_keys($cart))
                ->where('is_active', true)
                ->get()
                ->map(fn (Product $product) => (object) [
                    'id' => null,
                    'product' => $product,
                    'quantity' => $cart[$product->id] ?? 1,
                    'is_guest' => true,
                ])
                ->values();
        }

        return $this->cartQuery($request)
            ->with(['product.brand', 'product.category', 'product.images'])
            ->latest()
            ->get();
    }

    private function cartCount(Request $request): int
    {
        if (! $request->user()) {
            return array_sum($this->guestCart($request));
        }

        return (int) $this->cartQuery($request)->sum('quantity');
    }

    private function cartTotal(Request $request): float
    {
        return $this->cartItems($request)->sum(fn ($item) => (float) $item->product->active_price * $item->quantity);
    }

    private function ownsCartItem(Request $request, CartItem $cartItem): bool
    {
        $owner = $this->ownerAttributes($request);

        return collect($owner)->every(fn ($value, $key) => $cartItem->{$key} === $value);
    }

    private function guestCart(Request $request): array
    {
        return collect($request->session()->get('guest_cart', []))
            ->mapWithKeys(fn ($quantity, $productId) => [(int) $productId => max(1, min(99, (int) $quantity))])
            ->all();
    }

    private function guestCartQuantity(Request $request, Product $product): int
    {
        return $this->guestCart($request)[$product->id] ?? 0;
    }

    private function putGuestCartQuantity(Request $request, Product $product, int $quantity): void
    {
        $cart = $this->guestCart($request);
        $cart[$product->id] = max(1, min(99, $quantity));

        $request->session()->put('guest_cart', $cart);
    }

    private function applyProductFilters(Builder|HasMany $query, Request $request, bool $includeCategory = false): void
    {
        if ($includeCategory) {
            $category = trim((string) $request->query('category'));

            if ($category !== '') {
                $query->whereHas('category', fn (Builder $query) => $query->where('name', $category));
            }
        }

        $productType = trim((string) $request->query('product_type'));
        $property = trim((string) $request->query('property', (string) $request->query('properties')));
        $gender = trim((string) $request->query('gender'));
        $size = trim((string) $request->query('size'));

        if ($productType !== '') {
            $query->where('product_type', $productType);
        }

        if ($property !== '') {
            $query->whereJsonContains('properties', $property);
        }

        if ($gender !== '') {
            $query->where('gender', $gender);
        }

        if ($size !== '') {
            $query->where('size', $size);
        }

        $min = $request->query('price_min');
        $max = $request->query('price_max');

        if ($min !== null && $min !== '') {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [(float) $min]);
        }

        if ($max !== null && $max !== '') {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $max]);
        }
    }

    private function applyProductSorting(Builder|HasMany $query, string $sorting): void
    {
        match ($sorting) {
            'latest' => $query->latest(),
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            default => $query->latest(),
        };
    }

    private function categoryFilterOptions(): array
    {
        return [
            'category' => Category::orderBy('name')->pluck('name')->all(),
            'product_type' => Product::PRODUCT_TYPES,
            'property' => Product::PROPERTIES,
            'gender' => Product::GENDERS,
            'size' => Product::SIZES,
            'sort' => [
                'default' => 'Default',
                'latest' => 'Latest',
                'oldest' => 'Oldest',
                'price_asc' => 'Price Low to High',
                'price_desc' => 'Price High to Low',
            ],
        ];
    }

    private function makeOrderNumber(): string
    {
        do {
            $orderNumber = 'LB-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid());
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
