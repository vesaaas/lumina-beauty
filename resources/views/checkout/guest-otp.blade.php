@extends('layouts.app')

@section('title', 'Confirm Checkout Email - Lumina Beauty')

@section('content')
  <div class="account-overlay auth-challenge-overlay is-open" aria-hidden="false">
    @include('auth.partials.code-challenge', [
      'variant' => 'storefront',
      'eyebrow' => 'Checkout verification',
      'title' => 'Confirm your checkout email',
      'message' => 'Enter the 6-digit checkout code sent to',
      'email' => $email,
      'codeLabel' => 'Checkout code',
      'verifyRoute' => 'checkout.guest.otp.verify',
      'verifyButtonText' => 'Confirm and place order',
      'verifyLoadingText' => 'Confirming...',
      'resendRoute' => 'checkout.guest.otp.resend',
      'resendCooldownSeconds' => $resendCooldownSeconds,
      'cancelUrl' => route('checkout.index'),
    ])
  </div>
@endsection
