@extends('admin.layout')

@section('title', 'Products - Lumina Beauty Admin')
@section('heading', 'Products')
@section('eyebrow', 'Catalog management')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading">
      <h2>Product Management</h2>
      <a class="admin-link-button" href="{{ route('admin.products.create') }}"><i data-lucide="plus"></i> Add Product</a>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Image</th><th>Name</th><th>Brand</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
          @foreach ($products as $product)
            <tr>
              <td><img src="{{ $product->primaryImage() }}" alt="{{ $product->name }}" /></td>
              <td>{{ $product->name }}</td>
              <td>{{ $product->brand?->name }}</td>
              <td>{{ $product->category?->name }}</td>
              <td>{{ Number::currency($product->active_price, 'EUR') }}</td>
              <td>{{ $product->stock }}</td>
              <td>
                <div class="stacked-actions">
                  <a class="admin-link-button secondary" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                  <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                    @csrf
                    @method('DELETE')
                    <button class="admin-button danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="pagination">{{ $products->links() }}</div>
  </section>
@endsection
