@extends('layouts.app')

@section('title', 'Checkout - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Secure checkout" title="Checkout" copy="Complete your Lumina Beauty order. Orders are saved in the database and shown in the admin dashboard." />

  @if ($cartItems->isEmpty())
    <div class="empty-state"><i data-lucide="shopping-bag"></i><p>Your cart is empty.</p></div>
  @else
    @php($hasUnavailableItems = $cartItems->contains(fn ($item) => ! $item->product->isAvailableForPurchase() || $item->quantity > $item->product->stock))
    @if ($errors->any())
      <div class="form-errors">
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif
    <section class="checkout-layout">
      <form class="checkout-form" method="POST" action="{{ route('checkout.store') }}">
        @csrf
        <label>Name <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required /></label>
        <label>Email <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" required /></label>
        <label>Phone <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" /></label>
        <label>Address <input type="text" name="shipping_address" value="{{ old('shipping_address') }}" required /></label>
        <label>City <input type="text" name="shipping_city" value="{{ old('shipping_city') }}" required /></label>
        <label>Country <input type="text" name="shipping_country" value="{{ old('shipping_country', 'Kosovo') }}" required /></label>
        <button class="primary-button" type="submit" @disabled($hasUnavailableItems)><i data-lucide="credit-card"></i> Place Order</button>
      </form>

      <aside class="cart-summary checkout-summary">
        <span>Order total</span>
        <strong>{{ Number::currency($cartTotal, 'EUR') }}</strong>
        @foreach ($cartItems as $item)
          <p>
            {{ $item->product->name }} x {{ $item->quantity }}
            @if ($item->product->isOutOfStock())
              / Out of Stock
            @endif
          </p>
        @endforeach
      </aside>
    </section>
  @endif
@endsection
