@extends('layouts.app')

@section('title', 'Login - Lumina Beauty')

@section('content')
  <x-page-heading eyebrow="Customer access" title="Login" />

  <form class="auth-card" method="POST" action="{{ route('login.submit') }}">
    @csrf
    <label>Email <input type="email" name="email" placeholder="you@example.com" required /></label>
    <label>Password <input type="password" name="password" placeholder="Password" required /></label>
    <button class="primary-button" type="submit">Sign In</button>
  </form>
@endsection
