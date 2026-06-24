@extends('layouts.app')

@section('title', 'About Us - Lumina Beauty')

@section('content')
  <section class="split-section" data-scroll-reveal>
    <div class="trend-copy">
      <p class="eyebrow">About Us</p>
      <h1>Beauty shopping that feels calm, elegant, and personal.</h1>
      <p>Lumina Beauty curates skin care, hair care, makeup, fragrance, and body essentials for customers who want a clean and premium shopping experience.</p>
      <a class="primary-button" href="{{ route('contact') }}">Contact Us</a>
    </div>
  </section>
@endsection
