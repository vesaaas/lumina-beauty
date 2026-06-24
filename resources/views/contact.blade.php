@extends('layouts.app')

@section('title', 'Contact Us - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Contact Us" title="Contact Lumina Beauty" copy="Send us a question about products, shipping, or your Lumina Beauty account." />

  <form class="auth-card">
    <label>Name <input type="text" placeholder="Your name" /></label>
    <label>Email <input type="email" placeholder="you@example.com" /></label>
    <label>Message <textarea placeholder="How can we help?"></textarea></label>
    <button class="primary-button" type="button">Send Message</button>
  </form>
@endsection
