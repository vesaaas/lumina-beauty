@extends('admin.layout')

@section('title', $order->order_number.' - Lumina Beauty Admin')
@section('heading', $order->order_number)
@section('eyebrow', 'Order details')

@section('content')
  <section class="admin-grid two-columns">
    <article class="admin-panel">
      <div class="panel-heading"><h2>Customer</h2></div>
      <p><strong>{{ $order->customer_name }}</strong></p>
      <p>{{ $order->customer_email }}</p>
      <p>{{ $order->customer_phone }}</p>
      <p>{{ $order->shipping_address }}, {{ $order->shipping_city }}, {{ $order->shipping_country }}</p>
    </article>
    <article class="admin-panel">
      <div class="panel-heading"><h2>Status</h2></div>
      <p><span class="status-pill">{{ Str::title($order->status) }}</span></p>
      @if ($order->isTerminal())
        <p class="admin-muted">{{ Str::title($order->status) }} is a final status. This order can no longer be changed.</p>
      @else
        <form
          class="admin-form"
          method="POST"
          action="{{ route('admin.orders.update', $order) }}"
          data-requires-password-confirmation
          data-confirm-title="Update order status"
          data-confirm-message="Enter your current password to update this order status."
          data-confirm-button="Update Status"
          data-confirm-tone="neutral"
        >
          @csrf
          @method('PATCH')
          <label class="full">Move Status To
            <select name="status">
              <option value="{{ $order->status }}">{{ Str::title($order->status) }} (current)</option>
              @foreach ($order->availableTransitions() as $status)
                <option value="{{ $status }}">{{ Str::title($status) }}</option>
              @endforeach
            </select>
          </label>

          <div class="form-actions">
            <button class="admin-button" type="submit" data-password-confirm>Update Status</button>
          </div>
        </form>
      @endif
    </article>
  </section>

  <section class="admin-panel">
    <div class="panel-heading"><h2>Items</h2><strong>{{ Number::currency($order->total, 'EUR') }}</strong></div>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Unit</th><th>Qty</th><th>Total</th></tr></thead>
      <tbody>
        @foreach ($order->items as $item)
          <tr>
            <td>{{ $item->product_name }}</td>
            <td>{{ $item->brand_name }}</td>
            <td>{{ $item->category_name }}</td>
            <td>{{ Number::currency($item->unit_price, 'EUR') }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ Number::currency($item->line_total, 'EUR') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </section>
@endsection
