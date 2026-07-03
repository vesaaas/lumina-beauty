@extends('layouts.app')

@section('title', $category.' - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Category" :title="$category" :copy="'Products filtered by '.$category.'.'" />

  <x-premium-filter-bar :filters="$categoryFilters" :active="$activeFilters" />

  @php($hasActiveProductFilters = collect($activeFilters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty())

  <div id="product-results" @class(['product-grid', 'is-filtered-results' => $hasActiveProductFilters]) @if (! $hasActiveProductFilters) data-scroll-reveal-products @endif>
    @forelse ($filteredProducts as $product)
      <x-product-card :product="$product" />
    @empty
      <div class="empty-state"><i data-lucide="search-x"></i><p>No products match these filters.</p></div>
    @endforelse
  </div>
@endsection
