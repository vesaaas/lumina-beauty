@extends('layouts.app')

@section('title', 'Favorites - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Saved products" title="Favorites" copy="Products you mark with the heart icon appear here for quick browsing." />

  @if (count($favoriteProducts) > 0)
    <div class="product-grid favorite-grid" data-scroll-reveal-products>
      @foreach ($favoriteProducts as $product)
        <div class="saved-product">
          <x-product-card :product="$product" />
          <form method="POST" action="{{ route('favorites.destroy', $product['slug']) }}">
            @csrf
            @method('DELETE')
            <button class="remove-item-button" type="submit"><i data-lucide="x"></i> Remove</button>
          </form>
        </div>
      @endforeach
    </div>
  @else
    <div class="empty-state"><i data-lucide="heart"></i><p>No favorite products yet.</p></div>
  @endif
@endsection
