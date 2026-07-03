@props(['product'])

<article @class(['product-card', 'is-out-of-stock' => $product['is_out_of_stock']]) data-scroll-reveal-item data-product-card data-product-id="{{ $product['id'] }}" data-name="{{ strtolower($product['name'].' '.$product['brand'].' '.$product['category']) }}">
  <div class="product-media" data-card-gallery>
    <a class="media-link" href="{{ route('products.show', $product['slug']) }}" aria-label="Open {{ $product['name'] }}"></a>
    @if ($product['is_out_of_stock'])
      <span class="stock-badge">Out of Stock</span>
    @elseif (! empty($product['sale_price']))
      <span class="sale-badge">SALE</span>
    @endif
    @foreach ($product['images'] as $index => $image)
      <img class="{{ $index === 0 ? 'is-active' : '' }}" src="{{ $image }}" alt="{{ $product['name'] }}" loading="lazy" data-card-image />
    @endforeach
  </div>
  <form method="POST" action="{{ route('favorites.toggle', $product['slug']) }}">
    @csrf
    <button class="icon-button favorite-button" type="submit" aria-label="Add {{ $product['name'] }} to favorites">
      <i data-lucide="heart"></i>
    </button>
  </form>
  <div class="product-info">
    <div class="product-meta">
      <a href="{{ route('brands.show', Str::slug($product['brand'])) }}">{{ $product['brand'] }}</a>
      <a href="{{ route('categories.show', Str::slug($product['category'])) }}">{{ $product['category'] }}</a>
    </div>
    <h3><a href="{{ route('products.show', $product['slug']) }}">{{ $product['name'] }}</a></h3>
    <strong class="price">
      @if (! empty($product['sale_price']))
        <span>{{ Number::currency($product['sale_price'], 'EUR') }}</span>
        <del>{{ Number::currency($product['price'], 'EUR') }}</del>
      @else
        {{ Number::currency($product['price'], 'EUR') }}
      @endif
    </strong>
    <a class="product-read-more" href="{{ route('products.show', $product['slug']) }}">See More</a>
    @if ($product['is_available_for_purchase'])
      <form method="POST" action="{{ route('cart.add', $product['slug']) }}">
        @csrf
        <button class="secondary-button product-cart-button" type="submit"><i data-lucide="shopping-bag"></i> Add to Cart</button>
      </form>
    @else
      <button class="secondary-button product-cart-button" type="button" disabled><i data-lucide="shopping-bag"></i> Out of Stock</button>
    @endif
  </div>
</article>
