@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="auth-card">
            <h1>Verify your email</h1>

            <p>
                We sent a 6-digit verification code to
                <strong>{{ $email }}</strong>.
            </p>

            <p>
                The code expires in 10 minutes.
            </p>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('verification.otp.verify') }}">
                @csrf

                <label for="code">Verification code</label>

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
                >

                <button type="submit">
                    Verify email
                </button>
            </form>

            <div class="otp-resend-panel">
                <p>Didn't receive the code?</p>

                <form method="POST" action="{{ route('verification.otp.resend') }}">
                    @csrf

                    <button
                        type="submit"
                        class="secondary-button"
                        data-resend-button
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
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.querySelector('[data-resend-button]');

            if (! button) {
                return;
            }

            let seconds = Number.parseInt(button.dataset.cooldownSeconds || '0', 10);

            if (seconds <= 0) {
                return;
            }

            const updateButton = () => {
                if (seconds <= 0) {
                    button.disabled = false;
                    button.textContent = 'Resend code';
                    return;
                }

                button.disabled = true;
                button.textContent = `Resend code in ${seconds}s`;
                seconds -= 1;
                window.setTimeout(updateButton, 1000);
            };

            updateButton();
        });
    </script>
@endsection
