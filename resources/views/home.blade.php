@extends('layouts.app')

@section('title', 'Lumina Beauty - Home')

@section('content')
  <section class="hero-section home-section" data-scroll-reveal>
    <div class="hero-copy">
      <p class="eyebrow">Premium beauty storefront</p>
      <h1>Lumina Beauty</h1>
      <p>Curated skin care, hair care, makeup, perfume, and self-care essentials with a calm luxury shopping experience.</p>
      <div class="hero-actions">
        <a class="primary-button" href="#new-arrivals" data-scroll-products><i data-lucide="sparkles"></i> Shop Products</a>
      </div>
    </div>
    <div class="hero-visual" aria-label="Lumina Beauty campaign carousel" data-hero-carousel>
      @foreach ($heroSlides as $index => $slide)
        <img class="{{ $index === 0 ? 'is-active' : '' }}" src="{{ $slide }}" alt="Lumina Beauty curated beauty products" />
      @endforeach
    </div>
  </section>

  <section class="section-shell brand-strip-section home-section" data-scroll-reveal>
    <h2 class="home-section-title">Worldwide Brands</h2>
    @php
      $worldwideBrands = [
        'Dior',
        'YSL Beauty',
        'Chanel',
        'Guerlain',
        'Armani Beauty',
        'Lancôme',
        'Charlotte Tilbury',
        'Fenty Beauty',
        'Rare Beauty',
        'Hourglass',
        'NARS',
        'Tom Ford',
        'Jo Malone London',
        'Byredo',
        'Diptyque',
        'Maison Margiela',
        'La Mer',
        'Augustinus Bader',
        'La Roche-Posay',
        'The Ordinary',
        'Olaplex',
        'Kérastase',
        'Sol de Janeiro',
        'CeraVe',
      ];
    @endphp
    <div class="brand-carousel" aria-label="Worldwide brands">
      <div class="brand-carousel-track">
        @foreach ($worldwideBrands as $brand)
          <a class="brand-logo brand-logo-{{ Str::slug($brand) }}" href="{{ in_array($brand, $brands, true) ? route('brands.show', Str::slug($brand)) : route('brands.index') }}">
            <span>{{ $brand }}</span>
          </a>
        @endforeach
        @foreach ($worldwideBrands as $brand)
          <a class="brand-logo brand-logo-{{ Str::slug($brand) }}" href="{{ in_array($brand, $brands, true) ? route('brands.show', Str::slug($brand)) : route('brands.index') }}" aria-hidden="true" tabindex="-1">
            <span>{{ $brand }}</span>
          </a>
        @endforeach
      </div>
    </div>
    <div class="carousel-progress brand-carousel-progress" aria-hidden="true"><span></span></div>
  </section>

  <section class="section-shell home-section" id="new-arrivals" data-scroll-reveal data-scroll-reveal-products>
    <div class="section-heading product-section-heading">
      <h2>New Arrivals</h2>
      <a class="text-link" href="{{ route('sales') }}">See All Products</a>
    </div>
    <div class="product-carousel-shell" data-carousel-shell>
      <div class="product-carousel" data-product-carousel>
        @forelse ($newArrivals as $product)
          <x-product-card :product="$product" />
        @empty
          <div class="empty-state"><i data-lucide="sparkles"></i><p>No new arrivals are available right now.</p></div>
        @endforelse
      </div>
      <div class="carousel-progress" aria-hidden="true"><span data-carousel-progress></span></div>
    </div>
  </section>

  <section class="section-shell home-section" data-scroll-reveal data-scroll-reveal-products>
    <div class="section-heading product-section-heading">
      <h2>Sales</h2>
      <a class="text-link" href="{{ route('sales') }}">View Sales</a>
    </div>
    <div class="product-carousel-shell" data-carousel-shell>
      <div class="product-carousel" data-product-carousel>
        @forelse (array_slice($saleProducts, 0, 4) as $product)
          <x-product-card :product="$product" />
        @empty
          <div class="empty-state"><i data-lucide="badge-percent"></i><p>No sale products are available right now.</p></div>
        @endforelse
      </div>
      <div class="carousel-progress" aria-hidden="true"><span data-carousel-progress></span></div>
    </div>
  </section>

  <section class="section-shell category-section home-section" data-scroll-reveal>
    <h2 class="home-section-title">Categories</h2>
    <div class="luxury-category-strip" aria-label="Lumina Beauty categories">
      <a class="luxury-category-link luxury-category-skin" href="{{ route('categories.show', 'skin-care') }}" data-scroll-reveal-item style="--reveal-index: 0">
        <span class="luxury-category-name">Skin Care</span>
      </a>
      <a class="luxury-category-link luxury-category-hair" href="{{ route('categories.show', 'hair-care') }}" data-scroll-reveal-item style="--reveal-index: 1">
        <span class="luxury-category-name">Hair Care</span>
      </a>
      <a class="luxury-category-link luxury-category-makeup" href="{{ route('categories.show', 'makeup') }}" data-scroll-reveal-item style="--reveal-index: 2">
        <span class="luxury-category-name">Makeup</span>
      </a>
      <a class="luxury-category-link luxury-category-perfume" href="{{ route('categories.show', 'perfume') }}" data-scroll-reveal-item style="--reveal-index: 3">
        <span class="luxury-category-name">Perfume</span>
      </a>
      <a class="luxury-category-link luxury-category-body" href="{{ route('categories.show', 'body-care') }}" data-scroll-reveal-item style="--reveal-index: 4">
        <span class="luxury-category-name">Body Care</span>
      </a>
    </div>
  </section>

  <section class="section-shell about-preview home-section" data-scroll-reveal>
    <img src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1400&q=88" alt="Elegant Lumina Beauty makeup and self-care arrangement" loading="lazy" />
    <div>
      <p class="eyebrow">Discover Lumina Beauty</p>
      <h2>Take a closer look at our beauty story, curated products, and modern self-care essentials.</h2>
      <a class="primary-button" href="{{ route('about') }}">About Us</a>
    </div>
  </section>
@endsection
