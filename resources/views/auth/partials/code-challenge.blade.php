@props([
    'variant' => 'storefront',
    'eyebrow' => 'Account security',
    'title',
    'message',
    'email',
    'expiryText' => 'The code expires in 10 minutes.',
    'codeLabel' => 'Security code',
    'verifyRoute',
    'verifyButtonText' => 'Verify',
    'verifyLoadingText' => 'Verifying...',
    'resendRoute',
    'resendCooldownSeconds' => 0,
    'cancelRoute' => null,
    'cancelUrl' => null,
])

@php($isAdmin = $variant === 'admin')

<div class="{{ $isAdmin ? 'admin-login-shell' : 'auth-challenge-page' }}">
  <section
    class="{{ $isAdmin ? 'admin-login-panel auth-challenge-card' : 'account-modal auth-challenge-modal' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="auth-challenge-title"
    data-auth-dialog
  >
    @if ($cancelRoute)
      <form method="POST" action="{{ route($cancelRoute) }}" class="auth-dialog-close-form" data-secure-form>
        @csrf
        <button class="{{ $isAdmin ? 'admin-modal-close' : 'modal-close' }}" type="submit" data-auth-dialog-close aria-label="Close verification">
          <i data-lucide="x"></i>
        </button>
      </form>
    @elseif ($cancelUrl)
      <a class="{{ $isAdmin ? 'admin-modal-close' : 'modal-close' }}" href="{{ $cancelUrl }}" data-auth-dialog-close aria-label="Close verification">
        <i data-lucide="x"></i>
      </a>
    @endif

    <div class="{{ $isAdmin ? 'admin-login-heading' : 'modal-heading' }}">
      <p class="eyebrow">{{ $eyebrow }}</p>
      <h1 id="auth-challenge-title">{{ $title }}</h1>
      <p>{{ $message }} <strong>{{ $email }}</strong>.</p>
      <p>{{ $expiryText }}</p>
    </div>

    @if (session('status'))
      <p class="{{ $isAdmin ? 'admin-alert' : 'form-status' }}">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
      <div class="{{ $isAdmin ? 'admin-alert admin-alert-error' : 'form-errors' }}">
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <form class="{{ $isAdmin ? 'admin-login-form' : 'modal-form is-active' }}" method="POST" action="{{ route($verifyRoute) }}" data-secure-form>
      @csrf

      <label for="code">
        {{ $codeLabel }}
        <input
          id="code"
          type="text"
          name="code"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          pattern="[0-9]{6}"
          required
          autofocus
          data-sensitive-code
        >
      </label>

      <button
        class="{{ $isAdmin ? 'admin-button' : 'primary-button' }}"
        type="submit"
        data-submit-label="{{ $verifyLoadingText }}"
      >
        {{ $verifyButtonText }}
      </button>
    </form>

    <div class="otp-resend-panel">
      <p>Didn't receive the code?</p>

      <form method="POST" action="{{ route($resendRoute) }}" data-secure-form>
        @csrf

        <button
          type="submit"
          class="{{ $isAdmin ? 'admin-button secondary' : 'secondary-button' }}"
          data-resend-button
          data-submit-label="Sending..."
          data-cooldown-seconds="{{ $resendCooldownSeconds }}"
          @disabled($resendCooldownSeconds > 0)
        >
          @if ($resendCooldownSeconds > 0)
            Resend code in {{ $resendCooldownSeconds }}s
          @else
            Resend code
          @endif
        </button>
      </form>
    </div>
  </section>
</div>
