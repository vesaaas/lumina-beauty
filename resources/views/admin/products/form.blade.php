@extends('admin.layout')

@php($isEdit = $product->exists)
@section('title', ($isEdit ? 'Edit' : 'Add').' Product - Lumina Beauty Admin')
@section('heading', $isEdit ? 'Edit Product' : 'Add Product')
@section('eyebrow', 'Catalog management')

@section('content')
  <section class="admin-panel">
    <form
      class="admin-form"
      method="POST"
      action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
      enctype="multipart/form-data"
      data-requires-password-confirmation
      data-confirm-title="{{ $isEdit ? 'Update product' : 'Create product' }}"
      data-confirm-message="Enter your current password to {{ $isEdit ? 'update this product' : 'create this product' }}."
      data-confirm-button="{{ $isEdit ? 'Save Product' : 'Create Product' }}"
      data-confirm-tone="neutral"
    >
      @csrf
      @if ($isEdit)
        @method('PUT')
      @endif

      <label>Name <input type="text" name="name" value="{{ old('name', $product->name) }}" required /></label>
      <label>Price <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required /></label>
      <label>Sale Price <input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" /></label>
      <label>Stock <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required /></label>
      <label>Category
        <select name="category_id" required>
          @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
          @endforeach
        </select>
      </label>
      <label>Brand
        <select name="brand_id" required>
          @foreach ($brands as $brand)
            <option value="{{ $brand->id }}" @selected((int) old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
          @endforeach
        </select>
      </label>
      <label>Product Type
        <select name="product_type" required>
          @foreach ($filterOptions['product_type'] as $option)
            <option value="{{ $option }}" @selected(old('product_type', $product->product_type) === $option)>{{ $option }}</option>
          @endforeach
        </select>
      </label>
      <label>Gender
        <select name="gender" required>
          @foreach ($filterOptions['gender'] as $option)
            <option value="{{ $option }}" @selected(old('gender', $product->gender) === $option)>{{ $option }}</option>
          @endforeach
        </select>
      </label>
      <label>Size
        <select name="size" required>
          @foreach ($filterOptions['size'] as $option)
            <option value="{{ $option }}" @selected(old('size', $product->size) === $option)>{{ $option }}</option>
          @endforeach
        </select>
      </label>
      @php($selectedProperties = old('properties', $product->properties ?? []))
      <div class="full metadata-checks">
        <span>Properties</span>
        <div class="check-row">
          @foreach ($filterOptions['properties'] as $option)
            <label><input type="checkbox" name="properties[]" value="{{ $option }}" @checked(in_array($option, $selectedProperties, true)) /> {{ $option }}</label>
          @endforeach
        </div>
      </div>
      @foreach ([
        'skin_types' => 'Skin Types',
        'hair_types' => 'Hair Types',
        'concerns' => 'Concerns',
        'benefits' => 'Benefits',
        'target_areas' => 'Target Areas',
        'key_ingredients' => 'Key Ingredients',
      ] as $field => $label)
        @php($selectedValues = old($field.'_present') !== null ? old($field, []) : ($product->{$field} ?? []))
        <div class="full metadata-checks">
          <span>{{ $label }}</span>
          <input type="hidden" name="{{ $field }}_present" value="1" />
          <div class="check-row">
            @foreach ($filterOptions[$field] as $value => $optionLabel)
              <label><input type="checkbox" name="{{ $field }}[]" value="{{ $value }}" @checked(in_array($value, $selectedValues, true)) /> {{ $optionLabel }}</label>
            @endforeach
          </div>
        </div>
      @endforeach
      <label>Routine Step
        <select name="routine_step">
          <option value="">Unknown / not applicable</option>
          @foreach ($filterOptions['routine_steps'] as $value => $label)
            <option value="{{ $value }}" @selected(old('routine_step', $product->routine_step) === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Usage Frequency
        <select name="usage_frequency">
          <option value="">Unknown / not documented</option>
          @foreach ($filterOptions['usage_frequencies'] as $value => $label)
            <option value="{{ $value }}" @selected(old('usage_frequency', $product->usage_frequency) === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      @foreach ([
        'am_suitable' => 'AM suitable',
        'pm_suitable' => 'PM suitable',
      ] + $filterOptions['tri_state_attributes'] as $field => $label)
        @php($selectedState = old($field, $product->{$field} === null ? 'unknown' : ($product->{$field} ? '1' : '0')))
        <label>{{ $label }}
          <select name="{{ $field }}">
            <option value="unknown" @selected($selectedState === 'unknown')>Unknown</option>
            <option value="1" @selected($selectedState === '1')>Known yes</option>
            <option value="0" @selected($selectedState === '0')>Known no</option>
          </select>
        </label>
      @endforeach
      <label class="full">Usage Instructions <textarea name="usage_instructions" rows="3">{{ old('usage_instructions', $product->usage_instructions) }}</textarea></label>
      <label class="full">Works Well With <textarea name="works_well_with_text" rows="3">{{ old('works_well_with_text', implode("\n", $product->works_well_with ?? [])) }}</textarea></label>
      <label class="full">Avoid Combining With <textarea name="avoid_combining_with_text" rows="3">{{ old('avoid_combining_with_text', implode("\n", $product->avoid_combining_with ?? [])) }}</textarea></label>
      <label class="full">Knowledge Warnings <textarea name="knowledge_warnings" rows="3">{{ old('knowledge_warnings', $product->knowledge_warnings) }}</textarea></label>
      <label class="full">Description <textarea name="description" rows="5" required>{{ old('description', $product->description) }}</textarea></label>
      <label class="full">Product Images <input type="file" name="images[]" multiple accept="image/*" /></label>

      @if ($product->exists && $product->images->isNotEmpty())
        <div class="full admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Current Images</th><th>Path</th></tr></thead>
            <tbody>
              @foreach ($product->images as $image)
                <tr><td><img src="{{ str_starts_with($image->path, 'http') ? $image->path : Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt_text }}" /></td><td>{{ $image->path }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      <div class="check-row">
        <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) /> Featured</label>
        <label><input type="checkbox" name="is_new_arrival" value="1" @checked(old('is_new_arrival', $product->is_new_arrival)) /> New arrival</label>
        <label><input type="checkbox" name="is_hot_trend" value="1" @checked(old('is_hot_trend', $product->is_hot_trend)) /> Hot trend</label>
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) /> Active</label>
      </div>

      <div class="form-actions">
        <button class="admin-button" type="submit" data-password-confirm>{{ $isEdit ? 'Save Product' : 'Create Product' }}</button>
        <a class="admin-link-button secondary" href="{{ route('admin.products.index') }}">Cancel</a>
      </div>
    </form>
  </section>
@endsection
