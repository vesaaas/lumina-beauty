@extends('layouts.app')

@section('title', 'Contact Us - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Contact Us" title="Contact Lumina Beauty" copy="Send us a question about products, shipping, or your Lumina Beauty account." />

  <form class="auth-card" method="POST" action="{{ route('contact.send') }}">
    @csrf

    @if (session('contact_status'))
      <p class="form-status">{{ session('contact_status') }}</p>
    @endif

    @if ($errors->contact->any())
      <div class="form-errors">
        @foreach ($errors->contact->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <label>Name <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name" required /></label>
    <label>Email <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required /></label>
    <label>Topic <input type="text" name="topic" value="{{ old('topic') }}" placeholder="Product question, shipping, account..." required /></label>
    <label>Message <textarea name="message" placeholder="How can we help?" required>{{ old('message') }}</textarea></label>
    <button class="primary-button" type="submit">Send Message</button>
  </form>
@endsection
