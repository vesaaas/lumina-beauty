@extends('layouts.app')

@section('title', 'Sales - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Sale edit" title="Sales" copy="Selected Lumina Beauty products with current sale pricing." />

  <div class="product-grid" data-scroll-reveal-products>
    @foreach ($filteredProducts as $product)
      <x-product-card :product="$product" />
    @endforeach
  </div>
@endsection
