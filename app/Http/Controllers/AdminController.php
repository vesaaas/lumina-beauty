<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Mail\OrderStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $orders = Order::with('items')->latest()->take(8)->get();
        $totalSales = (float) Order::sum('total');
        $monthlyTarget = 2500;
        $monthlySales = (float) Order::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total');
        $targetProgress = $monthlyTarget > 0 ? min(100, round(($monthlySales / $monthlyTarget) * 100)) : 0;
        $revenueByMonth = Order::query()
            ->selectRaw('DATE_FORMAT(created_at, "%b") as label, SUM(total) as total')
            ->whereYear('created_at', now()->year)
            ->groupByRaw('DATE_FORMAT(created_at, "%b"), MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->pluck('total', 'label');
        $months = collect(range(1, 12))->map(fn (int $month) => now()->month($month)->format('M'));
        $revenueValues = $months->map(fn (string $label) => (float) ($revenueByMonth[$label] ?? 0));

        $topCategories = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();
        $lowStockProducts = Product::with(['brand', 'category'])
            ->where('is_active', true)
            ->orderBy('stock')
            ->take(5)
            ->get();
        $topSellingProducts = Product::with(['brand'])
            ->withSum('orderItems as sold_units', 'quantity')
            ->orderByDesc('sold_units')
            ->take(5)
            ->get();
        $recentCustomers = User::where('is_admin', false)
            ->latest()
            ->take(5)
            ->get();
        $pendingOrders = Order::where('status', 'pending')->count();
        $saleProductsCount = Product::whereNotNull('sale_price')->count();

        return view('admin.dashboard', [
            'totalSales' => $totalSales,
            'totalOrders' => Order::count(),
            'totalUsers' => User::where('is_admin', false)->count(),
            'totalProducts' => Product::count(),
            'pendingOrders' => $pendingOrders,
            'saleProductsCount' => $saleProductsCount,
            'monthlySales' => $monthlySales,
            'monthlyTarget' => $monthlyTarget,
            'targetProgress' => $targetProgress,
            'trafficSessions' => max(120, User::count() * 42 + Product::count() * 18),
            'conversionRate' => Product::count() > 0 ? round((Order::count() / max(Product::count(), 1)) * 100, 1) : 0,
            'averageOrderValue' => Order::count() > 0 ? $totalSales / Order::count() : 0,
            'recentOrders' => $orders,
            'lowStockProducts' => $lowStockProducts,
            'topSellingProducts' => $topSellingProducts,
            'recentCustomers' => $recentCustomers,
            'revenueLabels' => $months,
            'revenueValues' => $revenueValues,
            'topCategoryLabels' => $topCategories->pluck('name'),
            'topCategoryValues' => $topCategories->pluck('products_count'),
        ]);
    }

    public function products(): View
    {
        return view('admin.products.index', [
            'products' => Product::with(['brand', 'category', 'images'])->latest()->paginate(12),
        ]);
    }

    public function createProduct(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'filterOptions' => $this->productFilterOptions(),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $product = Product::create($this->productAttributes($request));
        $this->storeImages($request, $product);

        return redirect()->route('admin.products.index')->with('admin_status', 'Product created.');
    }

    public function editProduct(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product->load('images'),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'filterOptions' => $this->productFilterOptions(),
        ]);
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->productAttributes($request, $product));
        $this->storeImages($request, $product);

        return redirect()->route('admin.products.index')->with('admin_status', 'Product updated.');
    }

    public function categories(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        Category::create($attributes + ['slug' => Str::slug($attributes['name'])]);

        return back()->with('admin_status', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($attributes + ['slug' => Str::slug($attributes['name'])]);

        return back()->with('admin_status', 'Category updated.');
    }

    public function deleteCategory(Category $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, 'Move or delete products before deleting this category.');
        $category->delete();

        return back()->with('admin_status', 'Category deleted.');
    }

    public function brands(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brands,name'],
            'description' => ['nullable', 'string'],
        ]);

        Brand::create($attributes + ['slug' => Str::slug($attributes['name'])]);

        return back()->with('admin_status', 'Brand created.');
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brands,name,'.$brand->id],
            'description' => ['nullable', 'string'],
        ]);

        $brand->update($attributes + ['slug' => Str::slug($attributes['name'])]);

        return back()->with('admin_status', 'Brand updated.');
    }

    public function deleteBrand(Brand $brand): RedirectResponse
    {
        abort_if($brand->products()->exists(), 422, 'Move or delete products before deleting this brand.');
        $brand->delete();

        return back()->with('admin_status', 'Brand deleted.');
    }

    public function orders(): View
    {
        return view('admin.orders.index', [
            'orders' => Order::with('user')->latest()->paginate(15),
        ]);
    }

    public function order(Order $order): View
    {
        return view('admin.orders.show', ['order' => $order->load(['items', 'user'])]);
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $attributes = $request->validate([
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
        ]);

        $previousStatus = $order->status;
        $order->update($attributes);

        if ($previousStatus !== $order->status && in_array($order->status, ['processing', 'completed'], true)) {
            Mail::to($order->customer_email)->send(new OrderStatusNotification($order, $order->status));
        }

        return back()->with('admin_status', 'Order status updated.');
    }

    public function users(): View
    {
        return view('admin.users.index', [
            'users' => User::withCount('orders')->latest()->paginate(15),
        ]);
    }

    public function reports(): View
    {
        return view('admin.reports', [
            'orders' => Order::latest()->take(20)->get(),
            'revenue' => Order::sum('total'),
            'discounts' => Order::sum('discount_total'),
        ]);
    }

    public function discounts(): View
    {
        return view('admin.discounts', [
            'products' => Product::with(['brand', 'category'])->whereNotNull('sale_price')->latest()->get(),
        ]);
    }

    public function settings(): View
    {
        return view('admin.settings');
    }

    private function productAttributes(Request $request, ?Product $product = null): array
    {
        $attributes = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'product_type' => ['required', 'string', 'in:'.implode(',', Product::PRODUCT_TYPES)],
            'properties' => ['required', 'array', 'min:1'],
            'properties.*' => ['required', 'string', 'in:'.implode(',', Product::PROPERTIES)],
            'gender' => ['required', 'string', 'in:'.implode(',', Product::GENDERS)],
            'size' => ['required', 'string', 'in:'.implode(',', Product::SIZES)],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'images.*' => ['nullable', 'image', 'max:4096'],
        ]);

        $attributes['slug'] = $this->uniqueSlug($attributes['name'], $product);
        $attributes['is_featured'] = $request->boolean('is_featured');
        $attributes['is_new_arrival'] = $request->boolean('is_new_arrival');
        $attributes['is_hot_trend'] = $request->boolean('is_hot_trend');
        $attributes['is_active'] = $request->boolean('is_active');

        return $attributes;
    }

    private function productFilterOptions(): array
    {
        return [
            'product_type' => Product::PRODUCT_TYPES,
            'properties' => Product::PROPERTIES,
            'gender' => Product::GENDERS,
            'size' => Product::SIZES,
        ];
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Product::where('slug', $slug)->when($product, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function storeImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $nextSort = (int) $product->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $image) {
            if (! $image) {
                continue;
            }

            $path = $image->store('products', 'public');
            $product->images()->create([
                'path' => $path,
                'alt_text' => $product->name,
                'sort_order' => $nextSort++,
            ]);
        }
    }
}
