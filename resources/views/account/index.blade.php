@extends('layouts.app')

@section('title', 'Account - Lumina Beauty')

@section('content')
  <section class="account-window">
    <p class="eyebrow">Account</p>
    <h1>Your Lumina Beauty Account</h1>
    <p>Use the Account button in the header to login, register, or access the protected admin login.</p>
    <div class="account-actions-panel">
      <a class="primary-button" href="{{ route('login') }}"><i data-lucide="log-in"></i> Login</a>
      <a class="secondary-button" href="{{ route('register') }}"><i data-lucide="user-plus"></i> Register</a>
      <a class="secondary-button" href="{{ route('admin.dashboard') }}"><i data-lucide="layout-dashboard"></i> Admin Dashboard</a>
    </div>
  </section>
@endsection
