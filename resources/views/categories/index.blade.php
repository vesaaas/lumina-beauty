@extends('layouts.app')

@section('title', 'Categories - Lumina Beauty')

@section('content')
  <section class="category-section category-section-page" data-scroll-reveal>
    <h1 class="home-section-title">Categories</h1>
    <div class="category-showcase">
      @foreach ($categoryModels->take(4) as $category)
      <a class="category-card category-card-{{ $category->slug }}" href="{{ route('categories.show', $category) }}" data-scroll-reveal-item style="--reveal-index: {{ $loop->index }}">
        <span class="category-visual" aria-hidden="true"></span>
        <span class="category-name">{{ $category->name === 'Makeup' ? 'MAKE UP' : Str::upper($category->name) }}</span>
      </a>
      @endforeach
    </div>
  </section>
@endsection
