@extends('layouts.app')

@section('title', 'Hot Trends - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Beauty trends" title="Hot Trends" copy="A focused place for seasonal favorites, trending textures, and best-loved beauty rituals." />
  <div class="product-grid" data-scroll-reveal-products>
    @foreach ($hotTrendProducts as $product)
      <x-product-card :product="$product" />
    @endforeach
  </div>
@endsection
