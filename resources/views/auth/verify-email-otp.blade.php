@extends('layouts.app')

@section('title', 'Verify Email - Lumina Beauty')

@section('content')
  <div class="account-overlay auth-challenge-overlay is-open" aria-hidden="false">
    @include('auth.partials.code-challenge', [
      'variant' => 'storefront',
      'eyebrow' => 'Email verification',
      'title' => 'Verify your email',
      'message' => 'Enter the 6-digit code sent to',
      'email' => $email,
      'codeLabel' => 'Verification code',
      'verifyRoute' => 'verification.otp.verify',
      'verifyButtonText' => 'Verify email',
      'verifyLoadingText' => 'Verifying...',
      'resendRoute' => 'verification.otp.resend',
      'resendCooldownSeconds' => $resendCooldownSeconds,
      'cancelUrl' => route('home'),
    ])
  </div>
@endsection
