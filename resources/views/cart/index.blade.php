@extends('layouts.app')

@section('title', 'Shopping Cart - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Shopping bag" title="Shopping Cart" copy="Review your Lumina Beauty selections before checkout." />

  @if ($cartItems->isNotEmpty())
    @php
      $cartSubtotal = $cartItems->sum(fn ($item) => (float) $item->product->price * $item->quantity);
      $cartDiscount = max(0, $cartSubtotal - $cartTotal);
      $shippingTotal = 0;
      $grandTotal = $cartTotal + $shippingTotal;
      $hasUnavailableItems = $cartItems->contains(fn ($item) => ! $item->product->isAvailableForPurchase() || $item->quantity > $item->product->stock);
    @endphp
    @if ($errors->any())
      <div class="form-errors">
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif
    <div class="cart-page-shell">
      <section class="cart-table cart-bag-panel" aria-label="Shopping cart items">
        <div class="cart-panel-heading">
          <div>
            <span class="eyebrow">Selected products</span>
            <h2>Your Bag</h2>
          </div>
          <strong>{{ $cartItems->sum('quantity') }} {{ Str::plural('item', $cartItems->sum('quantity')) }}</strong>
        </div>
        <div class="cart-table-body">
          @foreach ($cartItems as $item)
            @php
              $product = $item->product;
              $isGuestItem = $item->is_guest ?? false;
              $updateRoute = $isGuestItem ? route('cart.guest.update', $product) : route('cart.update', $item);
              $removeRoute = $isGuestItem ? route('cart.guest.destroy', $product) : route('cart.destroy', $item);
              $unitPrice = (float) $product->active_price;
              $lineTotal = $unitPrice * $item->quantity;
            @endphp
            <article class="cart-row">
              <a class="cart-product" href="{{ route('products.show', $product) }}">
                <img src="{{ $product->primaryImage() }}" alt="{{ $product->name }}" />
                <span>
                  <strong>{{ $product->name }}</strong>
                  <small>
                    {{ $product->brand?->name }} / {{ $product->category?->name }}
                    @if ($product->isOutOfStock())
                      / Out of Stock
                    @endif
                  </small>
                </span>
              </a>
              <div class="cart-row-details">
                <div class="cart-price-block">
                  <span>Unit price</span>
                  <strong class="cart-price">
                    {{ Number::currency($unitPrice, 'EUR') }}
                    @if ($product->sale_price)
                      <del>{{ Number::currency($product->price, 'EUR') }}</del>
                    @endif
                  </strong>
                </div>
                <div class="cart-quantity-block">
                  <span>Quantity</span>
                  <form class="quantity-stepper" method="POST" action="{{ $updateRoute }}" aria-label="Change quantity for {{ $product->name }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" aria-label="Decrease quantity" @disabled($item->quantity <= 1 || $product->isOutOfStock())>−</button>
                    <span>{{ $item->quantity }}</span>
                    <button type="submit" name="quantity" value="{{ min(99, $item->quantity + 1) }}" aria-label="Increase quantity" @disabled($item->quantity >= 99 || $item->quantity >= $product->stock)>+</button>
                  </form>
                </div>
                <div class="cart-total-block">
                  <span>Line total</span>
                  <strong class="cart-line-total">{{ Number::currency($lineTotal, 'EUR') }}</strong>
                </div>
                <form class="cart-remove-form" method="POST" action="{{ $removeRoute }}">
                  @csrf
                  @method('DELETE')
                  <button class="remove-item-button" type="submit" aria-label="Remove {{ $product->name }}"><i data-lucide="trash-2"></i></button>
                </form>
              </div>
            </article>
          @endforeach
        </div>
      </section>
      <aside class="cart-summary cart-order-summary">
        <span class="eyebrow">Checkout details</span>
        <h2>Order Summary</h2>
        <div class="summary-line">
          <span>Subtotal</span>
          <strong>{{ Number::currency($cartSubtotal, 'EUR') }}</strong>
        </div>
        <div class="summary-line">
          <span>Shipping</span>
          <strong>{{ $shippingTotal > 0 ? Number::currency($shippingTotal, 'EUR') : 'Complimentary' }}</strong>
        </div>
        <div class="summary-line">
          <span>Discount</span>
          <strong>{{ $cartDiscount > 0 ? '-'.Number::currency($cartDiscount, 'EUR') : Number::currency(0, 'EUR') }}</strong>
        </div>
        <div class="summary-total">
          <span>Total</span>
          <strong>{{ Number::currency($grandTotal, 'EUR') }}</strong>
        </div>
        @if ($hasUnavailableItems)
          <button class="primary-button cart-checkout-button" type="button" disabled><i data-lucide="credit-card"></i> Review Stock</button>
        @else
          <a class="primary-button cart-checkout-button" href="{{ route('checkout.index') }}"><i data-lucide="credit-card"></i> Proceed to Checkout</a>
        @endif
        <p>Secure checkout with complimentary delivery on selected beauty essentials.</p>
      </aside>
    </div>
  @else
    <div class="empty-state"><i data-lucide="shopping-bag"></i><p>Your cart is empty.</p></div>
  @endif
@endsection
