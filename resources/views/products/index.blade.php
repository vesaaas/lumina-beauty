@extends('layouts.app')

@section('title', 'Products - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="All products" title="Products" :copy="request('search') ? 'Search results for '.request('search').'.' : 'Browse curated beauty products by brand, category, and ritual.'" />

  <x-premium-filter-bar :filters="$categoryFilters" :active="$activeFilters" show-category />

  @php($hasActiveProductFilters = collect($activeFilters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty())

  @if (count($filteredProducts) > 0)
    <div id="product-results" @class(['product-grid', 'is-filtered-results' => $hasActiveProductFilters]) @if (! $hasActiveProductFilters) data-scroll-reveal-products @endif>
      @foreach ($filteredProducts as $product)
        <x-product-card :product="$product" />
      @endforeach
    </div>
  @else
    <div id="product-results" class="empty-state"><i data-lucide="search-x"></i><p>No products found.</p></div>
  @endif
@endsection
