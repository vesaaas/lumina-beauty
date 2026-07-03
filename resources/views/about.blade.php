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
    <form class="auth-card" method="POST" action="{{ route('about.send') }}">
      @csrf

      @if (session('about_status'))
        <p class="form-status">{{ session('about_status') }}</p>
      @endif

      @if ($errors->about->any())
        <div class="form-errors">
          @foreach ($errors->about->all() as $error)
            <p>{{ $error }}</p>
          @endforeach
        </div>
      @endif

      <label>Name <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name" required /></label>
      <label>Email <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required /></label>
      <label>Message <textarea name="message" placeholder="Tell us what you want to know about Lumina Beauty." required>{{ old('message') }}</textarea></label>
      <button class="primary-button" type="submit">Send Message</button>
    </form>
  </section>
@endsection
