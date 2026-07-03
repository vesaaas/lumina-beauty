@extends('layouts.app')

@section('title', $product['name'].' - Lumina Beauty')

@section('content')
  <section class="product-detail" data-gallery>
    <div class="detail-gallery">
      <button class="gallery-arrow" type="button" data-image-prev aria-label="Previous image"><i data-lucide="chevron-left"></i></button>
      @foreach ($product['images'] as $index => $image)
        <img class="{{ $index === 0 ? 'is-active' : '' }}" src="{{ $image }}" alt="{{ $product['name'] }}" data-gallery-image />
      @endforeach
      <button class="gallery-arrow" type="button" data-image-next aria-label="Next image"><i data-lucide="chevron-right"></i></button>
    </div>
    <div class="detail-copy">
      <p class="eyebrow">{{ $product['category'] }} / {{ $product['brand'] }}</p>
      <h1>{{ $product['name'] }}</h1>
      @if ($product['is_out_of_stock'])
        <span class="stock-badge detail-stock-badge">Out of Stock</span>
      @endif
      <strong class="price">
        @if (! empty($product['sale_price']))
          <span>{{ Number::currency($product['sale_price'], 'EUR') }}</span>
          <del>{{ Number::currency($product['price'], 'EUR') }}</del>
        @else
          {{ Number::currency($product['price'], 'EUR') }}
        @endif
      </strong>
      <p>{{ $product['description'] }}</p>
      <div class="detail-actions">
        @if ($product['is_available_for_purchase'])
          <form method="POST" action="{{ route('cart.add', $product['slug']) }}">
            @csrf
            <input type="hidden" name="quantity" value="1" />
            <button class="primary-button" type="submit"><i data-lucide="shopping-bag"></i> Add to Cart</button>
          </form>
        @else
          <button class="primary-button" type="button" disabled><i data-lucide="shopping-bag"></i> Out of Stock</button>
        @endif
        <form method="POST" action="{{ route('favorites.toggle', $product['slug']) }}">
          @csrf
          <button class="secondary-button" type="submit"><i data-lucide="heart"></i> Add Favorite</button>
        </form>
      </div>
    </div>
  </section>
@endsection
