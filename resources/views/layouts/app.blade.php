<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Lumina Beauty - Premium Beauty Store')</title>
    <meta name="description" content="Lumina Beauty premium Laravel beauty e-commerce store." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://images.unsplash.com" />
    <link rel="preconnect" href="https://unpkg.com" />
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,500&family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/styles.css" />
  </head>
  <body>
    <div class="promo-strip top-promo-strip">Complimentary delivery on selected beauty essentials</div>

    <header class="site-header" id="top">
      <a class="brand" href="{{ route('home') }}" aria-label="Lumina Beauty home">
        <span class="brand-mark" aria-hidden="true"><i data-lucide="sparkles"></i></span>
        <span class="brand-name">Lumina Beauty</span>
      </a>

      <button class="icon-button mobile-menu-button" type="button" aria-label="Open menu" data-menu-toggle>
        <i data-lucide="menu"></i>
      </button>

      <nav class="main-nav" data-main-nav>
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('products.index') }}">Products</a>
        <div class="nav-dropdown">
          <button class="nav-trigger" type="button">Categories</button>
          <div class="dropdown-panel mega-panel category-mega">
            @foreach ($categories as $category)
              <a href="{{ route('categories.show', Str::slug($category)) }}">{{ $category }}</a>
            @endforeach
          </div>
        </div>
        <div class="nav-dropdown">
          <button class="nav-trigger" type="button">Brands</button>
          <div class="dropdown-panel mega-panel brand-mega">
            @foreach ($brands as $brand)
              <a href="{{ route('brands.show', Str::slug($brand)) }}">{{ $brand }}</a>
            @endforeach
          </div>
        </div>
        <a href="{{ route('hot-trends') }}">Hot Trends</a>
        <a href="{{ route('sales') }}">Sales</a>
        <a href="{{ route('about') }}">About Us</a>
        <a href="{{ route('contact') }}">Contact Us</a>
      </nav>

      <div class="header-actions">
        <a class="icon-button counter-button" href="{{ route('favorites.index') }}" aria-label="Favorites">
          <i data-lucide="heart"></i>
          @if (($favoritesCount ?? 0) > 0)
            <span data-favorites-count>{{ $favoritesCount }}</span>
          @endif
        </a>
        <a class="icon-button counter-button" href="{{ route('cart.index') }}" aria-label="Shopping cart">
          <i data-lucide="shopping-bag"></i>
          @if (($cartCount ?? 0) > 0)
            <span data-cart-count>{{ $cartCount }}</span>
          @endif
        </a>
        @auth
          <button class="account-link" type="button" data-account-open>
            <i data-lucide="user-round"></i>
            {{ Str::limit(auth()->user()->name, 12) }}
          </button>
        @else
          <button class="account-link" type="button" data-account-open>
            <i data-lucide="user-round"></i>
            Account
          </button>
        @endauth
      </div>
    </header>

    <section class="search-strip" aria-label="Product search">
      <form class="search-panel" action="{{ route('products.index') }}" method="GET" data-search-form>
        <i data-lucide="search"></i>
        <input name="search" value="{{ request('search') }}" type="search" placeholder="Search products, brands, categories..." autocomplete="off" data-search-input />
        <div class="search-results" data-search-results></div>
      </form>
    </section>

    <main data-storefront data-storefront-cart-count="{{ $cartCount ?? 0 }}" data-storefront-favorites-count="{{ $favoritesCount ?? 0 }}">
      @yield('content')
    </main>

    <script type="application/json" id="storefront-products-json">@json($products)</script>

    <div class="footer-tone-strip" aria-hidden="true"></div>

    <footer class="site-footer">
      <div class="footer-brand">
        <a class="brand" href="{{ route('home') }}">
          <span class="brand-mark" aria-hidden="true"><i data-lucide="sparkles"></i></span>
          <span class="brand-name">Lumina Beauty</span>
        </a>
        <p>Copyright © 2026 Lumina Beauty. All Rights Reserved.</p>
      </div>
      <div class="footer-column">
        <h3>Our Story</h3>
        <a href="{{ route('about') }}">About Us</a>
      </div>
      <div class="footer-column">
        <h3>Info</h3>
        <a href="{{ route('contact') }}">Contact</a>
        <a href="#">Terms of Use</a>
        <a href="#">FAQ</a>
        <a href="#">Shipping</a>
      </div>
      <div class="footer-column">
        <h3>Follow Us</h3>
        <a href="#">Instagram</a>
        <a href="#">TikTok</a>
        <a href="#">Facebook</a>
      </div>
      <div class="footer-column mailing-list">
        <h3>Join Our Mailing List</h3>
        <p>Get our newest products, sales, and updates delivered straight to your inbox! Sign up with your email today!</p>
        <form>
          <input type="email" placeholder="Email address" />
          <button class="primary-button" type="button">Join</button>
        </form>
      </div>
    </footer>

    <div class="account-overlay {{ $errors->any() || session('account_modal') ? 'is-open' : '' }}" data-account-modal id="account" aria-hidden="{{ $errors->any() || session('account_modal') ? 'false' : 'true' }}">
      <div class="account-modal" role="dialog" aria-modal="true" aria-label="Lumina Beauty account">
        <button class="modal-close" type="button" data-account-close aria-label="Close account modal"><i data-lucide="x"></i></button>
        <div class="modal-heading">
          <p class="eyebrow">Account access</p>
          <h2>Lumina Beauty</h2>
          @auth
            <p>You are signed in as {{ auth()->user()->name }}.</p>
          @else
            <p>Login, create a customer account, or enter the admin dashboard with a developer-created admin account.</p>
          @endauth
        </div>

        @if (session('status'))
          <p class="form-status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
          <div class="form-errors">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif

        @auth
          <div class="account-actions-panel">
            @if (auth()->user()->is_admin)
              <a class="primary-button" href="{{ route('admin.dashboard') }}"><i data-lucide="layout-dashboard"></i> Admin Dashboard</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="secondary-button" type="submit"><i data-lucide="log-out"></i> Logout</button>
            </form>
          </div>
        @else
          <div class="account-tabs" role="tablist">
            <button class="is-active" type="button" data-account-tab="login">User Login</button>
            <button type="button" data-account-tab="register">User Register</button>
            <button type="button" data-account-tab="admin">Admin Login</button>
          </div>
          <form class="modal-form is-active" method="POST" action="{{ route('login.submit') }}" data-account-panel="login">
            @csrf
            <label>Email <input type="email" name="email" value="{{ old('email') }}" required /></label>
            <label>Password <input type="password" name="password" required /></label>
            <label class="checkbox-label"><input type="checkbox" name="remember" value="1" /> Remember me</label>
            <a class="text-link" href="{{ route('password.request') }}">Forgot Password?</a>
            <button class="primary-button" type="submit">Login</button>
          </form>
          <form class="modal-form" method="POST" action="{{ route('register.submit') }}" data-account-panel="register">
            @csrf
            <label>First Name <input type="text" name="first_name" value="{{ old('first_name') }}" required /></label>
            <label>Last Name <input type="text" name="last_name" value="{{ old('last_name') }}" required /></label>
            <label>Email <input type="email" name="email" value="{{ old('email') }}" required /></label>
            <label>Phone Number <input type="tel" name="phone" value="{{ old('phone') }}" required /></label>
            <label>Password <input type="password" name="password" required minlength="8" /></label>
            <button class="primary-button" type="submit">Create Account</button>
          </form>
          <form class="modal-form" method="POST" action="{{ route('admin.login.submit') }}" data-account-panel="admin">
            @csrf
            <label>Admin Email <input type="email" name="email" value="{{ old('email') }}" required /></label>
            <label>Password <input type="password" name="password" required /></label>
            <button class="primary-button" type="submit">Open Dashboard</button>
          </form>
        @endauth
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="/assets/js/storefront.js"></script>
  </body>
</html>
