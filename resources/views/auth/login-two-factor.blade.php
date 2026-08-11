@php($isAdmin = $context === \App\Models\LoginTwoFactorChallenge::CONTEXT_ADMIN)

@extends($isAdmin ? 'layouts.admin-auth' : 'layouts.app')

@section('title', 'Security Verification - Lumina Beauty')

@section('content')
  @if ($isAdmin)
    @include('auth.partials.code-challenge', [
      'variant' => 'admin',
      'eyebrow' => 'Admin security',
      'title' => 'Security verification',
      'message' => 'Enter the 6-digit admin code sent to',
      'email' => $email,
      'codeLabel' => 'Admin security code',
      'verifyRoute' => 'admin.login.2fa.verify',
      'verifyButtonText' => 'Verify and open dashboard',
      'verifyLoadingText' => 'Verifying...',
      'resendRoute' => 'admin.login.2fa.resend',
      'resendCooldownSeconds' => $resendCooldownSeconds,
      'cancelRoute' => 'admin.login.2fa.cancel',
    ])
  @else
    <div class="account-overlay auth-challenge-overlay is-open" aria-hidden="false">
      @include('auth.partials.code-challenge', [
        'variant' => 'storefront',
        'eyebrow' => 'Account security',
        'title' => 'Security verification',
        'message' => 'Enter the 6-digit code sent to',
        'email' => $email,
        'codeLabel' => 'Security code',
        'verifyRoute' => 'login.2fa.verify',
        'verifyButtonText' => 'Verify and sign in',
        'verifyLoadingText' => 'Verifying...',
        'resendRoute' => 'login.2fa.resend',
        'resendCooldownSeconds' => $resendCooldownSeconds,
        'cancelRoute' => 'login.2fa.cancel',
      ])
    </div>
  @endif
@endsection
