@extends('admin.layout')

@section('title', 'Categories - Lumina Beauty Admin')
@section('heading', 'Categories')
@section('eyebrow', 'Catalog taxonomy')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading"><h2>Add Category</h2></div>
    <form class="admin-form" method="POST" action="{{ route('admin.categories.store') }}">
      @csrf
      <label>Name <input type="text" name="name" required /></label>
      <label class="full">Description <textarea name="description" rows="3"></textarea></label>
      <div class="form-actions"><button class="admin-button" type="submit">Create Category</button></div>
    </form>
  </section>
  <section class="admin-panel">
    <div class="panel-heading"><h2>Manage Categories</h2></div>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Products</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach ($categories as $category)
          <tr>
            <td>
              <form class="inline-form" method="POST" action="{{ route('admin.categories.update', $category) }}" data-change-tracked-form>
                @csrf
                @method('PUT')
                <input type="text" name="name" value="{{ $category->name }}" required />
                <input type="hidden" name="description" value="{{ $category->description }}" />
                <button class="admin-button secondary" type="submit" data-change-tracked-submit disabled>Save</button>
              </form>
            </td>
            <td>{{ $category->products_count }}</td>
            <td>
              <form
                method="POST"
                action="{{ route('admin.categories.destroy', $category) }}"
                data-requires-password-confirmation
                data-confirm-title="Delete category"
                data-confirm-message="Enter your current password to delete {{ $category->name }}. Categories with products will not be deleted."
                data-confirm-button="Delete Category"
              >
                @csrf
                @method('DELETE')
                <button class="admin-button danger" type="submit" data-password-confirm>Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </section>
@endsection
