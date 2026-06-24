@extends('admin.layout')

@section('title', 'Orders - Lumina Beauty Admin')
@section('heading', 'Orders')
@section('eyebrow', 'Sales operations')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading"><h2>All Orders</h2></div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Order</th><th>Customer</th><th>Email</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
        <tbody>
          @forelse ($orders as $order)
            <tr>
              <td>{{ $order->order_number }}</td>
              <td>{{ $order->customer_name }}</td>
              <td>{{ $order->customer_email }}</td>
              <td><span class="status-pill">{{ $order->status }}</span></td>
              <td>{{ Number::currency($order->total, 'EUR') }}</td>
              <td>{{ $order->created_at->format('d M Y H:i') }}</td>
              <td><a class="admin-link-button secondary" href="{{ route('admin.orders.show', $order) }}">View</a></td>
            </tr>
          @empty
            <tr><td colspan="7">No orders yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="pagination">{{ $orders->links() }}</div>
  </section>
@endsection
