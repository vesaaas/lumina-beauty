@extends('layouts.app')

@section('title', 'Brands - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Curated beauty brands" title="Brands" copy="Explore international beauty names selected for the Lumina Beauty catalog." />

  <div class="brand-grid" data-scroll-reveal>
    @foreach ($brandModels as $brand)
      @php($brandProduct = $brand->products->first())
      <a class="brand-card" href="{{ route('brands.show', $brand) }}" data-scroll-reveal-item style="--reveal-index: {{ $loop->index }}">
        <img src="{{ $brandProduct?->primaryImage() ?? 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=85' }}" alt="{{ $brand->name }} product" loading="lazy" />
        <span>{{ $brand->name }}</span>
      </a>
    @endforeach
  </div>
@endsection
