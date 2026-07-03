@extends('admin.layout')

@section('title', 'Admin Dashboard - Lumina Beauty')
@section('heading', 'Dashboard')
@section('eyebrow', 'Business overview')

@section('content')
  <section class="dashboard-hero">
    <div>
      <p class="eyebrow">Lumina Beauty commerce</p>
      <h2>Premium store performance at a glance.</h2>
      <span>Track orders, customers, sales, products, categories, and discounts from one polished admin workspace.</span>
    </div>
    <div class="hero-actions">
      <a class="admin-link-button secondary" href="{{ route('admin.orders.index') }}"><i data-lucide="receipt-text"></i> Orders</a>
      <a class="admin-link-button" href="{{ route('admin.products.create') }}"><i data-lucide="plus"></i> Add Product</a>
    </div>
  </section>

  <section class="metric-grid">
    <article class="metric-card">
      <span class="metric-icon"><i data-lucide="euro"></i></span>
      <div><p>Total Sales</p><strong>{{ Number::currency($totalSales, 'EUR') }}</strong></div>
      <small>All-time revenue</small>
    </article>
    <article class="metric-card">
      <span class="metric-icon rose"><i data-lucide="shopping-bag"></i></span>
      <div><p>Total Orders</p><strong>{{ $totalOrders }}</strong></div>
      <small>{{ $pendingOrders }} pending for review</small>
    </article>
    <article class="metric-card">
      <span class="metric-icon cream"><i data-lucide="users"></i></span>
      <div><p>Customers</p><strong>{{ $totalUsers }}</strong></div>
      <small>Registered user accounts</small>
    </article>
    <article class="metric-card">
      <span class="metric-icon black"><i data-lucide="package"></i></span>
      <div><p>Products</p><strong>{{ $totalProducts }}</strong></div>
      <small>{{ $saleProductsCount }} products currently on sale</small>
    </article>
  </section>

  <section class="dashboard-grid">
    <article class="admin-panel revenue-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Analytics</p>
          <h2>Revenue Analytics</h2>
        </div>
        <span>{{ now()->year }}</span>
      </div>
      <canvas data-chart="revenue" data-labels='@json($revenueLabels)' data-values='@json($revenueValues)' height="260"></canvas>
    </article>

    <article class="admin-panel target-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Monthly target</p>
          <h2>Sales Goal</h2>
        </div>
      </div>
      <div class="progress-ring" style="--progress: {{ $targetProgress }}">
        <strong>{{ $targetProgress }}%</strong>
        <span>Reached</span>
      </div>
      <div class="target-copy">
        <strong>{{ Number::currency($monthlySales, 'EUR') }}</strong>
        <span>of {{ Number::currency($monthlyTarget, 'EUR') }} monthly target</span>
      </div>
    </article>

    <article class="admin-panel category-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Catalog</p>
          <h2>Top Categories</h2>
        </div>
      </div>
      <canvas data-chart="categories" data-labels='@json($topCategoryLabels)' data-values='@json($topCategoryValues)' height="220"></canvas>
    </article>

    <article class="admin-panel summary-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Revenue summary</p>
          <h2>Store Pulse</h2>
        </div>
      </div>
      <div class="summary-list">
        <div><span>Average order</span><strong>{{ Number::currency($averageOrderValue, 'EUR') }}</strong></div>
        <div><span>Pending orders</span><strong>{{ $pendingOrders }}</strong></div>
        <div><span>Sale products</span><strong>{{ $saleProductsCount }}</strong></div>
      </div>
    </article>

    <article class="admin-panel low-stock-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Inventory</p>
          <h2>Low Stock Products</h2>
        </div>
        <a href="{{ route('admin.products.index') }}">Manage</a>
      </div>
      <div class="compact-list">
        @forelse ($lowStockProducts as $product)
          <a href="{{ route('admin.products.edit', $product) }}">
            <span>
              <strong>{{ $product->name }}</strong>
              <small>{{ $product->brand?->name }} / {{ $product->category?->name }}</small>
            </span>
            <em>{{ $product->stock }} left</em>
          </a>
        @empty
          <p class="dashboard-empty">No active products yet.</p>
        @endforelse
      </div>
    </article>

    <article class="admin-panel top-products-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Merchandising</p>
          <h2>Top Selling Products</h2>
        </div>
      </div>
      <div class="compact-list">
        @forelse ($topSellingProducts as $product)
          <a href="{{ route('admin.products.edit', $product) }}">
            <span>
              <strong>{{ $product->name }}</strong>
              <small>{{ $product->brand?->name ?? 'Brand pending' }}</small>
            </span>
            <em>{{ (int) ($product->sold_units ?? 0) }} sold</em>
          </a>
        @empty
          <p class="dashboard-empty">Top sellers will appear after orders are placed.</p>
        @endforelse
      </div>
    </article>

    <article class="admin-panel customers-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Customers</p>
          <h2>Recent Customers</h2>
        </div>
        <a href="{{ route('admin.users.index') }}">View all</a>
      </div>
      <div class="compact-list">
        @forelse ($recentCustomers as $customer)
          <a href="{{ route('admin.users.index') }}">
            <span>
              <strong>{{ $customer->name }}</strong>
              <small>{{ $customer->email }}</small>
            </span>
            <em>{{ $customer->created_at->format('d M') }}</em>
          </a>
        @empty
          <p class="dashboard-empty">New registered customers will appear here.</p>
        @endforelse
      </div>
    </article>

    <article class="admin-panel orders-panel">
      <div class="panel-heading">
        <div>
          <p class="eyebrow">Operations</p>
          <h2>Recent Orders</h2>
        </div>
        <a href="{{ route('admin.orders.index') }}">View all</a>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
          <tbody>
            @forelse ($recentOrders as $order)
              <tr>
                <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->customer_name }}</td>
                <td><span class="status-pill">{{ $order->status }}</span></td>
                <td>{{ Number::currency($order->total, 'EUR') }}</td>
                <td>{{ $order->created_at->format('d M Y') }}</td>
              </tr>
            @empty
              <tr><td colspan="5">No orders yet. New checkout orders will appear here.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </section>
@endsection

@section('scripts')
  <script>
    const pixelRatio = window.devicePixelRatio || 1;

    const chartData = (canvas) => ({
      labels: JSON.parse(canvas.dataset.labels || "[]"),
      values: JSON.parse(canvas.dataset.values || "[]").map(Number),
    });

    const sizeCanvas = (canvas, height) => {
      const width = Math.max(canvas.offsetWidth, 320);
      canvas.width = width * pixelRatio;
      canvas.height = height * pixelRatio;
      const ctx = canvas.getContext("2d");
      ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
      return { ctx, width, height };
    };

    const drawRevenue = (canvas) => {
      const { labels, values } = chartData(canvas);
      const { ctx, width, height } = sizeCanvas(canvas, 260);
      const max = Math.max(...values, 1);
      const padding = { top: 24, right: 24, bottom: 42, left: 42 };
      const chartWidth = width - padding.left - padding.right;
      const chartHeight = height - padding.top - padding.bottom;
      const step = chartWidth / Math.max(values.length - 1, 1);

      ctx.clearRect(0, 0, width, height);
      ctx.strokeStyle = "#f0e0e4";
      ctx.lineWidth = 1;
      for (let i = 0; i < 5; i += 1) {
        const y = padding.top + (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(width - padding.right, y);
        ctx.stroke();
      }

      const points = values.map((value, index) => ({
        x: padding.left + index * step,
        y: padding.top + chartHeight - (value / max) * chartHeight,
      }));

      const gradient = ctx.createLinearGradient(0, padding.top, 0, height - padding.bottom);
      gradient.addColorStop(0, "rgba(185, 63, 104, .24)");
      gradient.addColorStop(1, "rgba(185, 63, 104, 0)");

      ctx.beginPath();
      points.forEach((point, index) => index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y));
      ctx.lineTo(points.at(-1)?.x || padding.left, height - padding.bottom);
      ctx.lineTo(padding.left, height - padding.bottom);
      ctx.closePath();
      ctx.fillStyle = gradient;
      ctx.fill();

      ctx.beginPath();
      points.forEach((point, index) => index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y));
      ctx.strokeStyle = "#b93f68";
      ctx.lineWidth = 3;
      ctx.lineCap = "round";
      ctx.stroke();

      points.forEach((point) => {
        ctx.beginPath();
        ctx.arc(point.x, point.y, 4, 0, Math.PI * 2);
        ctx.fillStyle = "#8e294d";
        ctx.fill();
      });

      ctx.fillStyle = "#756a70";
      ctx.font = "12px Manrope";
      labels.forEach((label, index) => {
        if (index % 2 === 0) ctx.fillText(label, padding.left + index * step - 10, height - 14);
      });
    };

    const drawCategories = (canvas) => {
      const { labels, values } = chartData(canvas);
      const { ctx, width, height } = sizeCanvas(canvas, 220);
      const max = Math.max(...values, 1);
      const barGap = 14;
      const barHeight = 20;
      ctx.clearRect(0, 0, width, height);
      labels.forEach((label, index) => {
        const y = 24 + index * (barHeight + barGap);
        const valueWidth = ((width - 150) * (values[index] / max));
        ctx.fillStyle = "#756a70";
        ctx.font = "12px Manrope";
        ctx.fillText(label, 0, y + 15);
        ctx.fillStyle = "#f8eef1";
        ctx.fillRect(122, y, width - 150, barHeight);
        ctx.fillStyle = index === 0 ? "#8e294d" : "#b93f68";
        ctx.fillRect(122, y, valueWidth, barHeight);
        ctx.fillStyle = "#21191d";
        ctx.fillText(String(values[index]), width - 18, y + 15);
      });
    };

    document.querySelectorAll("[data-chart='revenue']").forEach(drawRevenue);
    document.querySelectorAll("[data-chart='categories']").forEach(drawCategories);
    window.addEventListener("resize", () => {
      document.querySelectorAll("[data-chart='revenue']").forEach(drawRevenue);
      document.querySelectorAll("[data-chart='categories']").forEach(drawCategories);
    });
  </script>
@endsection
