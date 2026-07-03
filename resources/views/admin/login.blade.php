<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Admin Login - Lumina Beauty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/admin.css" />
  </head>
  <body class="admin-login-body">
    <main class="admin-login-shell">
      <section class="admin-login-panel">
        <a class="admin-brand login-brand" href="{{ route('home') }}">
          <span>LB</span>
          <strong>Lumina Beauty</strong>
          <small>Admin access</small>
        </a>

        <div class="admin-login-heading">
          <p class="eyebrow">Admin login</p>
          <h1>Open Dashboard</h1>
          <p>Enter your admin credentials to go directly to the Lumina Beauty dashboard.</p>
        </div>

        @if ($errors->any())
          <div class="admin-alert admin-alert-error">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif

        <form class="admin-login-form" method="POST" action="{{ route('admin.login.submit') }}">
          @csrf
          <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus /></label>
          <label>Password <input type="password" name="password" required /></label>
          <button class="admin-button" type="submit">Open Dashboard</button>
        </form>
      </section>
    </main>
  </body>
</html>
