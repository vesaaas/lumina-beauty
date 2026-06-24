@extends('layouts.app')

@section('title', 'Register - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Create account" title="Register" />

  <form class="auth-card" method="POST" action="{{ route('register.submit') }}">
    @csrf
    <label>Name <input type="text" name="name" placeholder="Your name" required /></label>
    <label>Email <input type="email" name="email" placeholder="you@example.com" required /></label>
    <label>Password <input type="password" name="password" placeholder="Password" required minlength="8" /></label>
    <label>Confirm Password <input type="password" name="password_confirmation" placeholder="Password" required minlength="8" /></label>
    <button class="primary-button" type="submit">Create Account</button>
  </form>
@endsection
