@extends('admin.layout')

@section('title', 'Reports - Lumina Beauty Admin')
@section('heading', 'Reports')
@section('eyebrow', 'Statistics')

@section('content')
  <section class="metric-grid">
    <article><span>Revenue</span><strong>{{ Number::currency($revenue, 'EUR') }}</strong></article>
    <article><span>Discounts Given</span><strong>{{ Number::currency($discounts, 'EUR') }}</strong></article>
    <article><span>Orders Tracked</span><strong>{{ $orders->count() }}</strong></article>
    <article><span>Average Order</span><strong>{{ Number::currency($orders->avg('total') ?? 0, 'EUR') }}</strong></article>
  </section>
  <section class="admin-panel">
    <div class="panel-heading"><h2>Latest Sales Report</h2></div>
    <table class="admin-table">
      <thead><tr><th>Order</th><th>Status</th><th>Subtotal</th><th>Discount</th><th>Total</th></tr></thead>
      <tbody>
        @forelse ($orders as $order)
          <tr>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->status }}</td>
            <td>{{ Number::currency($order->subtotal, 'EUR') }}</td>
            <td>{{ Number::currency($order->discount_total, 'EUR') }}</td>
            <td>{{ Number::currency($order->total, 'EUR') }}</td>
          </tr>
        @empty
          <tr><td colspan="5">No report data yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </section>
@endsection
