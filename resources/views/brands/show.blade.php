@extends('layouts.app')

@section('title', $brand.' - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Brand" :title="$brand" :copy="'Products filtered by '.$brand.'.'" />

  <div class="product-grid" data-scroll-reveal-products>
    @foreach ($filteredProducts as $product)
      <x-product-card :product="$product" />
    @endforeach
  </div>
@endsection
