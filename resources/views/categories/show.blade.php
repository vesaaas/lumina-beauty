@extends('layouts.app')

@section('title', $category.' - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Category" :title="$category" :copy="'Products filtered by '.$category.'.'" />

  <form class="category-filter-bar" method="GET" action="{{ url()->current() }}" data-auto-filter data-scroll-reveal>
    <label>
      <span>Product Type</span>
      <select name="product_type">
        <option value="">All Types</option>
        @foreach ($categoryFilters['product_type'] as $option)
          <option value="{{ $option }}" @selected(($activeFilters['product_type'] ?? '') === $option)>{{ $option }}</option>
        @endforeach
      </select>
    </label>
    <label>
      <span>Properties</span>
      <select name="property">
        <option value="">All Properties</option>
        @foreach ($categoryFilters['property'] as $option)
          <option value="{{ $option }}" @selected(($activeFilters['property'] ?? '') === $option)>{{ $option }}</option>
        @endforeach
      </select>
    </label>
    <label>
      <span>Gender</span>
      <select name="gender">
        <option value="">All</option>
        @foreach ($categoryFilters['gender'] as $option)
          <option value="{{ $option }}" @selected(($activeFilters['gender'] ?? '') === $option)>{{ $option }}</option>
        @endforeach
      </select>
    </label>
    <label>
      <span>Size</span>
      <select name="size">
        <option value="">All Sizes</option>
        @foreach ($categoryFilters['size'] as $option)
          <option value="{{ $option }}" @selected(($activeFilters['size'] ?? '') === $option)>{{ $option }}</option>
        @endforeach
      </select>
    </label>
    <div class="price-filter">
      <span>Price Filter</span>
      <div>
        <input type="number" name="price_min" min="0" step="1" placeholder="Min" value="{{ $activeFilters['price_min'] ?? '' }}" />
        <input type="number" name="price_max" min="0" step="1" placeholder="Max" value="{{ $activeFilters['price_max'] ?? '' }}" />
      </div>
    </div>
    <label>
      <span>Sorting</span>
      <select name="sort">
        @foreach ($categoryFilters['sort'] as $value => $label)
          <option value="{{ $value }}" @selected(($activeFilters['sort'] ?? 'default') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <button class="filter-apply-button" type="submit">Apply</button>
    @if (collect($activeFilters)->filter()->isNotEmpty())
      <a class="filter-reset-link" href="{{ url()->current() }}">Reset</a>
    @endif
  </form>

  <div class="product-grid" data-scroll-reveal-products>
    @forelse ($filteredProducts as $product)
      <x-product-card :product="$product" />
    @empty
      <div class="empty-state"><i data-lucide="search-x"></i><p>No products match these filters.</p></div>
    @endforelse
  </div>
@endsection
