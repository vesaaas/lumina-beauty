@extends('layouts.app')

@section('title', 'Products - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="All products" title="Products" :copy="request('search') ? 'Search results for '.request('search').'.' : 'Browse curated beauty products by brand, category, and ritual.'" />

  @if (count($filteredProducts) > 0)
    <div class="product-grid" data-scroll-reveal-products>
      @foreach ($filteredProducts as $product)
        <x-product-card :product="$product" />
      @endforeach
    </div>
  @else
    <div class="empty-state"><i data-lucide="search-x"></i><p>No products found.</p></div>
  @endif
@endsection
