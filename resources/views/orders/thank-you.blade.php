@extends('layouts.app')

@section('title', 'Order Placed - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Thank you" title="Order {{ $order->order_number }}" copy="Your order has been saved and is available for the admin to review." />

  <section class="cart-layout">
    <div class="cart-list">
      @foreach ($order->items as $item)
        <article class="cart-item">
          <div>
            <h3>{{ $item->product_name }}</h3>
            <p>{{ $item->brand_name }} / {{ $item->category_name }}</p>
            <strong>{{ Number::currency($item->unit_price, 'EUR') }} x {{ $item->quantity }}</strong>
          </div>
        </article>
      @endforeach
    </div>
    <aside class="cart-summary">
      <span>Status</span>
      <strong>{{ Str::title($order->status) }}</strong>
      <span>Total</span>
      <strong>{{ Number::currency($order->total, 'EUR') }}</strong>
      <a class="primary-button" href="{{ route('products.index') }}">Continue Shopping</a>
    </aside>
  </section>
@endsection
