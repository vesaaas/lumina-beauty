@extends('admin.layout')

@section('title', 'Brands - Lumina Beauty Admin')
@section('heading', 'Brands')
@section('eyebrow', 'Catalog taxonomy')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading"><h2>Add Brand</h2></div>
    <form
      class="admin-form"
      method="POST"
      action="{{ route('admin.brands.store') }}"
      data-requires-password-confirmation
      data-confirm-title="Create brand"
      data-confirm-message="Enter your current password to create this brand."
      data-confirm-button="Create Brand"
      data-confirm-tone="neutral"
    >
      @csrf
      <label>Name <input type="text" name="name" required /></label>
      <label class="full">Description <textarea name="description" rows="3"></textarea></label>
      <div class="form-actions"><button class="admin-button" type="submit" data-password-confirm>Create Brand</button></div>
    </form>
  </section>
  <section class="admin-panel">
    <div class="panel-heading"><h2>Manage Brands</h2></div>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Products</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach ($brands as $brand)
          <tr>
            <td>
              <form
                class="inline-form"
                method="POST"
                action="{{ route('admin.brands.update', $brand) }}"
                data-change-tracked-form
                data-requires-password-confirmation
                data-confirm-title="Update brand"
                data-confirm-message="Enter your current password to update {{ $brand->name }}."
                data-confirm-button="Save Brand"
                data-confirm-tone="neutral"
              >
                @csrf
                @method('PUT')
                <input type="text" name="name" value="{{ $brand->name }}" required />
                <input type="hidden" name="description" value="{{ $brand->description }}" />
                <button class="admin-button secondary" type="submit" data-change-tracked-submit data-password-confirm disabled>Save</button>
              </form>
            </td>
            <td>{{ $brand->products_count }}</td>
            <td>
              <form
                method="POST"
                action="{{ route('admin.brands.destroy', $brand) }}"
                data-requires-password-confirmation
                data-confirm-title="Delete brand"
                data-confirm-message="Enter your current password to delete {{ $brand->name }}. Brands assigned to products will not be deleted."
                data-confirm-button="Delete Brand"
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
