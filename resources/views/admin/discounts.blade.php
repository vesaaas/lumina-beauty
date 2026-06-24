@extends('admin.layout')

@section('title', 'Discounts - Lumina Beauty Admin')
@section('heading', 'Discounts')
@section('eyebrow', 'Sales area')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading">
      <h2>Products on Sale</h2>
      <a class="admin-link-button" href="{{ route('admin.products.create') }}">Add Product</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Original</th><th>Sale</th></tr></thead>
      <tbody>
        @forelse ($products as $product)
          <tr>
            <td><a href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a></td>
            <td>{{ $product->brand?->name }}</td>
            <td>{{ $product->category?->name }}</td>
            <td>{{ Number::currency($product->price, 'EUR') }}</td>
            <td>{{ Number::currency($product->sale_price, 'EUR') }}</td>
          </tr>
        @empty
          <tr><td colspan="5">No sale products yet. Add a sale price while editing a product.</td></tr>
        @endforelse
      </tbody>
    </table>
  </section>
@endsection
