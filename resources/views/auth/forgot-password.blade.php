<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - Lumina Beauty</title>
    <link rel="stylesheet" href="/assets/css/styles.css" />
  </head>
  <body>
    <main class="password-page">
      <section class="password-panel">
        <p class="eyebrow">Account recovery</p>
        <h1>Forgot Password?</h1>
        <p>Enter your customer account email and we will send a secure reset link.</p>

        @if (session('status'))
          <p class="form-status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
          <div class="form-errors">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif

        <form class="modal-form is-active" method="POST" action="{{ route('password.email') }}">
          @csrf
          <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus /></label>
          <button class="primary-button" type="submit">Send Reset Link</button>
        </form>

        <a class="text-link" href="{{ route('home') }}#account">Back to login</a>
      </section>
    </main>
  </body>
</html>
