<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password - Lumina Beauty</title>
    <link rel="stylesheet" href="/assets/css/styles.css" />
  </head>
  <body>
    <main class="password-page">
      <section class="password-panel">
        <p class="eyebrow">Account recovery</p>
        <h1>Reset Password</h1>
        <p>Create a new password for your Lumina Beauty customer account.</p>

        @if ($errors->any())
          <div class="form-errors">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif

        <form class="modal-form is-active" method="POST" action="{{ route('password.update') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}" />
          <label>Email <input type="email" name="email" value="{{ old('email', $email) }}" required /></label>
          <label>New Password <input type="password" name="password" required minlength="8" /></label>
          <label>Confirm New Password <input type="password" name="password_confirmation" required minlength="8" /></label>
          <button class="primary-button" type="submit">Reset Password</button>
        </form>
      </section>
    </main>
  </body>
</html>
