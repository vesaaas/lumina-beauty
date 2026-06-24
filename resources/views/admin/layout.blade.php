<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Lumina Beauty Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/admin.css" />
  </head>
  <body>
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <a class="admin-brand" href="{{ route('admin.dashboard') }}">
        <span>LB</span>
        <strong>Lumina Beauty</strong>
        <small>Commerce studio</small>
      </a>

      <nav>
        <p>Overview</p>
        <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        <a class="{{ request()->routeIs('admin.reports') ? 'is-active' : '' }}" href="{{ route('admin.reports') }}"><i data-lucide="chart-column"></i> Reports</a>

        <p>Management</p>
        <a class="{{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}"><i data-lucide="package"></i> Products</a>
        <a class="{{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.orders.index') }}"><i data-lucide="receipt-text"></i> Orders</a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}"><i data-lucide="users"></i> Customers</a>
        <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}"><i data-lucide="tags"></i> Categories</a>
        <a class="{{ request()->routeIs('admin.brands.*') ? 'is-active' : '' }}" href="{{ route('admin.brands.index') }}"><i data-lucide="badge"></i> Brands</a>

        <p>Growth</p>
        <a class="{{ request()->routeIs('admin.discounts') ? 'is-active' : '' }}" href="{{ route('admin.discounts') }}"><i data-lucide="badge-percent"></i> Discounts</a>
        <a class="{{ request()->routeIs('admin.settings') ? 'is-active' : '' }}" href="{{ route('admin.settings') }}"><i data-lucide="settings"></i> Settings</a>
      </nav>

      <div class="sidebar-card">
        <span>Premium mode</span>
        <strong>Beauty analytics</strong>
        <a href="{{ route('home') }}"><i data-lucide="store"></i> Storefront</a>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div class="admin-title-block">
          <p class="eyebrow">@yield('eyebrow', 'Admin area')</p>
          <h1>@yield('heading', 'Dashboard')</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-icon-link" href="{{ route('admin.products.create') }}" aria-label="Add product"><i data-lucide="plus"></i></a>
          <a class="admin-icon-link" href="{{ route('admin.orders.index') }}" aria-label="Orders"><i data-lucide="bell"></i></a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i data-lucide="log-out"></i> Logout</button>
          </form>
        </div>
      </header>

      @if (session('admin_status'))
        <div class="admin-alert">{{ session('admin_status') }}</div>
      @endif

      @if (isset($errors) && $errors->any())
        <div class="admin-alert admin-alert-error">
          @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
          @endforeach
        </div>
      @endif

      @yield('content')
    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
      if (window.lucide) window.lucide.createIcons();
    </script>
    @yield('scripts')
  </body>
</html>
